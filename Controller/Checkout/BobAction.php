<?php

namespace Fisrv\Payment\Controller\Checkout;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class BobAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private CheckoutCreator $checkoutCreator;
    private GetActionContext $action;
    private OrderFactory $orderFactory;

    public function __construct(
        CheckoutCreator $checkoutCreator,
        GetActionContext $action,
        OrderFactory $orderFactory
    ) {
        $this->checkoutCreator = $checkoutCreator;
        $this->action = $action;
        $this->orderFactory = $orderFactory;
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

    private function validateOrder()
    {
        $sign = $this->action->getRequest()->getParam('_nonce', false);
        $orderId = $this->action->getRequest()->getParam('order', false);
        $order = $this->orderFactory->create()->loadByIncrementId(intval($orderId));

        // if (!$order instanceof Order) {
        //     return false;
        // }

        // print_r(json_encode($order->getData(), JSON_PRETTY_PRINT));

        if (!$sign) {
            echo ('<br/>No nonce given, cancelling auth.');
            return false;
        }

        $sign = base64_decode($sign);
        $digest = $this->action->createSignature($order);

        echo '</br>';
        echo $sign;
        echo '</br>';
        echo $digest;

        return hash_equals($digest, $sign);
    }

    public function execute()
    {
        echo 'Bob Result: <br/>';
        if ($this->validateOrder()) {
            echo '<br/>Validation passed.';
        } else {
            echo '<br/>Validation failed.';
        }

        return $this->action->getResponse();
    }
}