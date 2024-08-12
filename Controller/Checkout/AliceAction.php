<?php

namespace Fisrv\Payment\Controller\Checkout;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;


class AliceAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private CheckoutCreator $checkoutCreator;
    private GetActionContext $action;
    private OrderRepository $orderRepository;

    public function __construct(
        CheckoutCreator $checkoutCreator,
        GetActionContext $action,
        OrderRepository $orderRepository
    ) {
        $this->checkoutCreator = $checkoutCreator;
        $this->action = $action;
        $this->orderRepository = $orderRepository;
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

    public function execute()
    {
        $order = $this->orderRepository->get(12);
        // $secret = $this->action->createKey($order);

        // print_r($order);

        // if ($order instanceof Order) {
        //     $order->setData('some', 'value');
        //     $order->setData('customer_note', 'value');
        // }
        // return $this->action->getResponse();

        $url = $this->action->getUrl('bobaction', true, [
            'order' => $order->getId(),
            '_nonce' => base64_encode($this->action->createSignature($order)),
            '_secure' => 'true',
        ]);

        $resultRedirect = $this->action->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);

        $this->orderRepository->save($order);
        return $resultRedirect;
    }
}