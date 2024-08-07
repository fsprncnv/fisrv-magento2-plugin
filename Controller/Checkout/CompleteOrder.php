<?php

namespace Fisrv\Payment\Controller\Checkout;

use Exception;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;


class CompleteOrder implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private CheckoutCreator $checkoutCreator;
    private InvoiceService $invoiceService;
    private TransactionFactory $transactionFactory;
    private OrderRepository $orderRepository;
    private GetActionContext $action;

    public function __construct(
        CheckoutCreator $checkoutCreator,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        OrderRepository $orderRepository,
        GetActionContext $action
    ) {
        $this->checkoutCreator = $checkoutCreator;
        $this->invoiceService = $invoiceService;
        $this->orderRepository = $orderRepository;
        $this->transactionFactory = $transactionFactory;
        $this->action = $action;
    }

    private function forceInvoicable(Order $order)
    {
        $order->setActionFlag(Order::ACTION_FLAG_INVOICE, true);
    }

    private function validateOrder()
    {
        return true;
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

    private function completeOrder()
    {
        $order = $this->action->getSession()->getLastRealOrder();
        $this->action->getLogger()->write('Attempting manual order completion of order ' . $order->getId());
        $this->action->getLogger()->write('Payment Method ' . $order->getPayment()->getMethod());

        try {
            if (!$order instanceof Order) {
                throw new Exception('Order was not retrieved properly on CompleteOrder');
            }

            if (!$order->canInvoice()) {
                throw new Exception('Order cannot invoice');
            }

            if (!$order->getState() === ORDER::STATE_NEW) {
                throw new Exception('Order does not have state new');
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
        } catch (\Throwable $th) {
            $this->action->getLogger()->write('CompleteOrder exception: ' . $th->getMessage());
            return null;
        }

        $this->action->getLogger()->write('Order status is ' . $order->getStatus());
        $this->action->getLogger()->write('Order state is ' . $order->getState());
        $this->action->getLogger()->write('--- End CompleteOrder ---');
    }

    public function execute()
    {
        $this->action->getLogger()->write('--- Run CompleteOrder action ---');
        $this->validateOrder();
        $this->completeOrder();

        return $this->action->_redirect('checkout/onepage/success?utm_nooverride=1', [
            '_secure' => true,
        ]);
    }
}