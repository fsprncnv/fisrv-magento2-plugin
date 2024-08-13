<?php

namespace Fisrv\Payment\Controller\Checkout;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;

/**
 * GET rest route which is triggered on failure page from
 * Fiserv Checkout. Processes order cancellation.
 */
class CancelOrder implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private OrderRepository $orderRepository;
    private OrderContext $context;

    public function __construct(
        OrderRepository $orderRepository,
        OrderContext $context
    ) {
        $this->orderRepository = $orderRepository;
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

    /**
     * Process order cancellation and possibly display error payload passed as query
     * from Fiserv.
     * 
     * {@inheritDoc}
     */
    public function execute()
    {
        $orderId = $this->context->getRequest()->getParam('order_id');
        $order = $this->context->getOrderRepository()->get($orderId);

        $this->context->getLogger()->write($this->context->getRequest()->getContent());

        if ($order instanceof Order) {
            $order->setState(Order::STATE_CANCELED);
            $order->setStatus(Order::STATE_CANCELED);
            $this->orderRepository->save($order);
        }

        $this->context->getLogger()->write(_('Checkout failure message:'));
        $message = $this->context->getRequest()->getParams();

        $this->context->getLogger()->write($message);
        $messageToDisplay = _('Sorry, something went wrong. Try another payment method.');

        if (isset($message['message'])) {
            $messageToDisplay = $message['message'];
        }

        $this->context->messageManager->addErrorMessage($messageToDisplay);
        $this->context->getLogger()->write($messageToDisplay, 'error');

        return $this->context->_redirect('checkout/cart', [
            '_query' => [
                '_secure' => 'true',
                'order_cancelled' => 'true'
            ]
        ]);
    }
}