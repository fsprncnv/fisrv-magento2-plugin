<?php

declare(strict_types=1);

namespace Fiserv\Checkout\Block\Adminhtml\Order;

use Fiserv\Checkout\Controller\Checkout\OrderContext;
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

    /**
     * This overide method injects into layout rendering.
     * This is used to inject a button component (used as refund button) without affecting
     * other modules.
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View $view Block component of view
     */
    public function beforeSetLayout(CoreView $view): void
    {
        $refundRoute = $this->context->getUrl(
            'refundaction',
            true,
            [
            'order_id' => $view->getOrderId()
            ]
        );

        $detailsRoute = $this->context->getUrl(
            'detailsaction',
            true,
            [
            'order_id' => $view->getOrderId()
            ]
        );

        $payment = $view->getOrder()->getPayment();

        if (is_null($payment)) {
            return;
        }

        if (!str_starts_with($payment->getMethod(), 'fisrv_')
            || $view->getOrder()->getStatus() !== Order::STATE_COMPLETE
        ) {
            return;
        }

        $refundMessage = _('Do you want to refund this order?');
        $view->addButton(
            'refundaction',
            [
                'label' => __('Refund'),
                'onclick' => "confirmSetLocation('{$refundMessage}', '{$refundRoute}')"
                ]
            );
            

        $detailsMessage = _('Checkout Details: ');
        $view->addButton(
            'detailsaction',
            [
                'label' => __('Checkout Details'),
                'onclick' => "confirmSetLocation('{$detailsMessage}', '{$detailsRoute}')"
            ]
        );
    }
}
