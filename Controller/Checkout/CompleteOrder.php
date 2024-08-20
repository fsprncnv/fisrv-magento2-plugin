<?php

namespace Fisrv\Payment\Controller\Checkout;

use Exception;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
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
    private InvoiceService $invoiceService;

    private TransactionFactory $transactionFactory;

    private OrderContext $action;

    public function __construct(
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        OrderContext $action,
    ) {
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->action = $action;
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

        if (!$referrer || $referrer !== $this->action->getConfigData()->getCheckoutHost()) {
            $this->action->getLogger()->write('Bad referrer, cancelling auth.');
            $this->action->getLogger()->write('REFERRER: ' . $referrer);
            $this->action->getLogger()->write('STORED HOST: ' . $this->action->getConfigData()->getCheckoutHost());

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

        if ($order->getState() !== ORDER::STATE_NEW) {
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
        $this->action->getOrderRepository()->save($order);

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

            $order = $this->action->getOrderRepository()->get($orderId);

            if (!($order instanceof Order)) {
                throw new Exception('Order could not be retrieved');
            }

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