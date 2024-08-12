<?php

namespace Fisrv\Payment\Block\Adminhtml\Order;

use Fisrv\Payment\Controller\Checkout\OrderContext;
use Magento\Sales\Block\Adminhtml\Order\View as CoreView;
use Magento\Sales\Model\Order;

class View
{
    private OrderContext $context;

    public function __construct(
        OrderContext $context
    ) {
        $this->context = $context;
    }

    public function beforeSetLayout(CoreView $view)
    {
        $message = _('Do you want to refund this order?');
        $url = $this->context->getUrl('refundaction', true, [
            'order_id' => $view->getOrderId()
        ]);

        if (
            !str_starts_with($view->getOrder()->getPayment()->getMethod(), 'fisrv_') ||
            $view->getOrder()->getStatus() !== Order::STATE_COMPLETE
        ) {
            return;
        }

        $view->addButton(
            'order_myaction',
            [
                'label' => __('Refund'),
                'onclick' => "confirmSetLocation('{$message}', '{$url}')"
            ]
        );
    }
}