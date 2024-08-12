<?php

namespace Fisrv\Payment\Controller\Checkout;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;


class CancelOrder implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private OrderRepository $orderRepository;
    private OrderContext $action;

    public function __construct(
        OrderRepository $orderRepository,
        OrderContext $action
    ) {
        $this->orderRepository = $orderRepository;
        $this->action = $action;
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
        $order = $this->action->getSession()->getLastRealOrder();

        if (is_null($order) && $order instanceof Order) {
            $order->setState(Order::STATE_CANCELED);
            $order->setStatus(Order::STATE_CANCELED);
            $this->orderRepository->save($order);
        }

        $this->action->getLogger()->write(_('Checkout failure message:'));
        $message = $this->action->getRequest()->getParams();

        $this->action->getLogger()->write($message);
        $messageToDisplay = _('Sorry, something went wrong. Try another payment method.');

        if (isset($message['message'])) {
            $messageToDisplay = $message['message'];
        }

        $this->action->messageManager->addErrorMessage($messageToDisplay);
        $this->action->getLogger()->write($messageToDisplay, 'error');

        return $this->action->_redirect('checkout/cart', [
            '_query' => [
                '_secure' => 'true',
                'order_cancelled' => 'true'
            ]
        ]);
    }
}