<?php

namespace Fisrv\Payment\Controller\Checkout;

use Exception;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\App\Action\Action;
use Fisrv\Payment\Logger\DebugLogger;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\OrderRepository;


class CompleteOrder extends Action
{
    private DebugLogger $logger;
    private CheckoutCreator $checkoutCreator;
    private Session $session;
    private InvoiceService $invoiceService;
    private TransactionFactory $transactionFactory;
    private OrderRepository $orderRepository;

    public function __construct(
        Context $context,
        Redirect $resultRedirectFactory,
        DebugLogger $logger,
        CheckoutCreator $checkoutCreator,
        Session $session,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        OrderRepository $orderRepository,
    ) {
        $this->logger = $logger;
        $this->checkoutCreator = $checkoutCreator;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->session = $session;
        $this->invoiceService = $invoiceService;
        $this->orderRepository = $orderRepository;
        $this->transactionFactory = $transactionFactory;

        parent::__construct($context);
    }

    private function forceInvoicable(Order $order)
    {
        $order->setActionFlag(Order::ACTION_FLAG_INVOICE, true);
    }

    private function logOrderCanInvoiceState(Order $order): void
    {
        $orderState = $order->getState();

        $this->logger->write('* Run check order canInvoice *');
        $this->logger->write(new DataObject([
            'canUnhold' => $order->canUnhold(),
            'isPaymentReview' => $order->isPaymentReview(),
            'isCanceled' => $order->isCanceled(),
            'isStateComplete' => $orderState === Order::STATE_COMPLETE,
            'isStateClosed' => $orderState === Order::STATE_CLOSED,
            'isNotActionFlagInvoice' => !$order->getActionFlag(Order::ACTION_FLAG_INVOICE),
        ]));
    }

    private function completeOrder()
    {
        $order = $this->session->getLastRealOrder();
        $this->logger->write('Attempting manual order completion of order ' . $order->getId());
        $this->logger->write('Payment Method ' . $order->getPayment()->getMethod());

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
            $this->logger->write('CompleteOrder exception: ' . $th->getMessage());
            return null;
        }

        $this->logger->write('Order status is ' . $order->getStatus());
        $this->logger->write('Order state is ' . $order->getState());
        $this->logger->write('--- End CompleteOrder ---');
    }

    public function execute()
    {
        $this->logger->write('--- Run CompleteOrder action ---');
        $this->completeOrder();
        return $this->_redirect('checkout/onepage/success?utm_nooverride=1', [
            '_secure' => true,
        ]);
    }
}