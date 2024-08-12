<?php

namespace Fisrv\Payment\Controller\Checkout;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;

class RefundAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private CheckoutCreator $checkoutCreator;
    private OrderContext $context;

    public function __construct(
        CheckoutCreator $checkoutCreator,
        OrderContext $context,
    ) {
        $this->checkoutCreator = $checkoutCreator;
        $this->context = $context;
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

    private function refund(Order $order)
    {
        return true;
    }

    public function execute()
    {
        $orderId = $this->context->getRequest()->getParam('order_id');
        $order = $this->context->getOrderRepository()->get($orderId);
        echo 'This is a refund of order: ' . $order->getId();

        if ($this->refund($order)) {
            echo 'Order refunded successfully.';
        }

        return $this->context->getResponse();
    }
}