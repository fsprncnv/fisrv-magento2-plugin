<?php

namespace Fisrv\Payment\Controller\Checkout;

use Exception;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Service\OrderService;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;

/**
 * GET rest route which is triggered on success page from
 * Fiserv Checkout. Processes order completion.
 */
class CompleteOrder implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private CheckoutCreator $checkoutCreator;
    private InvoiceService $invoiceService;
    private TransactionFactory $transactionFactory;
    private OrderRepository $orderRepository;
    private OrderFactory $orderFactory;
    private OrderContext $action;
    private CreditmemoService $memoService;
    private OrderService $orderService;

    private const REFERRER_URL = 'https://ci.checkout-lane.com/';

    public function __construct(
        CheckoutCreator $checkoutCreator,
        InvoiceService $invoiceService,
        CreditmemoService $memoService,
        TransactionFactory $transactionFactory,
        OrderRepository $orderRepository,
        OrderFactory $orderFactory,
        OrderContext $action
    ) {
        $this->checkoutCreator = $checkoutCreator;
        $this->invoiceService = $invoiceService;
        $this->memoService = $memoService;
        $this->orderRepository = $orderRepository;
        $this->transactionFactory = $transactionFactory;
        $this->orderFactory = $orderFactory;
        $this->action = $action;
    }

    private function forceInvoicable(Order $order)
    {
        $order->setActionFlag(Order::ACTION_FLAG_INVOICE, true);
    }

    /**
     * Verify message signature on completion request.
     * If message signature is rejected stop handling this order.
     *
     * @param Order $order Order which has to be verified
     */
    private function authenticate(Order $order): bool
    {
        $sign = $this->action->getRequest()->getParam('_nonce', false);
        $referrer = $this->action->getRequest()->getHeader('Referer');

        if (!$referrer || $referrer !== self::REFERRER_URL) {
            $this->action->getLogger()->write('Bad referrer, cancelling auth.');
            return false;
        }

        if (!$sign) {
            $this->action->getLogger()->write('No signature given, cancelling auth.');
            return false;
        }

        $sign = base64_decode($sign);
        $digest = $this->action->createSignature($order);

        return hash_equals($digest, $sign);
    }


    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * Complete order. Create invoice to register completion state.
     *
     * @param Order $order Order to be set to complete
     */
    private function completeOrder(Order $order)
    {
        if (!$order instanceof Order) {
            throw new Exception(_('Order could not be retrieved'));
        }

        if (!$order->canInvoice()) {
            throw new Exception(_('Invoice cannot be created for this order'));
        }

        if (!$order->getState() === ORDER::STATE_NEW) {
            throw new Exception(_('Order has invalid state'));
        }

        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->capture();

        $order
            ->setState(Order::STATE_COMPLETE)
            ->setStatus(Order::STATE_COMPLETE);

        $transaction = $this->transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transaction->save();
        $this->orderRepository->save($order);

        $this->action->getLogger()->write('Order status is ' . $order->getStatus());
        $this->action->getLogger()->write('Order state is ' . $order->getState());
    }

    public function execute()
    {
        try {
            $orderId = $this->action->getRequest()->getParam('order_id', false);

            if (!$orderId) {
                throw new Exception(_('Order could not be retrieved'));
            }

            $order = $this->orderRepository->get($orderId);

            if (!$this->authenticate($order)) {
                throw new Exception(_('Authorization failed. Could not validate request.'));
            }

            $this->completeOrder($order);

            return $this->action->_redirect('checkout/onepage/success', [
                '_query' => [
                    '_secure' => 'true',
                    'utm_nooverride' => 'true'
                ]
            ]);

        } catch (\Throwable $th) {
            $this->action->messageManager->addErrorMessage($th->getMessage());
            $this->action->getLogger()->write('Order completion failed: ' . $th->getMessage());

            return $this->action->_redirect('checkout/cart', [
                '_query' => [
                    '_secure' => 'true',
                    'order_cancelled' => 'true'
                ]
            ]);
        }
    }
}