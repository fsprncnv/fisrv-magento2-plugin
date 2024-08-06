<?php

namespace Fisrv\Payment\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Fisrv\Payment\Logger\DebugLogger;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\OrderRepository;

class CancelOrder extends Action
{
    private DebugLogger $logger;
    private CheckoutCreator $checkoutCreator;
    private Session $session;
    private InvoiceService $invoiceService;
    private OrderRepository $orderRepository;
    private Registry $registry;

    public function __construct(
        Context $context,
        DebugLogger $logger,
        Session $session,
        OrderRepository $orderRepository,
    ) {
        $this->logger = $logger;
        $this->session = $session;
        $this->orderRepository = $orderRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->session->getLastRealOrder();

        if (is_null($order) && $order instanceof Order) {
            $order->setState(Order::STATE_CANCELED);
            $order->setStatus(Order::STATE_CANCELED);
            $this->orderRepository->save($order);
        }

        $this->logger->write('Checkout failure message:');
        $message = $this->getRequest()->getParams();

        $this->logger->write($message);

        $messageToDisplay = 'Order has been cancelled';

        if (isset($message['message'])) {
            $messageToDisplay = $message['message'];
        }

        $this->messageManager->addErrorMessage($messageToDisplay);
        $this->logger->write($messageToDisplay, 'error');

        return $this->_redirect('checkout/cart', [
            '_secure=true',
            'cancelled=true',
        ]);
    }
}