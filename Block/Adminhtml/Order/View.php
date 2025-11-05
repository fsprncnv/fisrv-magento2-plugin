<?php

declare(strict_types=1);

namespace Fiserv\Checkout\Block\Adminhtml\Order;

use Fiserv\Checkout\Controller\Checkout\OrderContext;
use Magento\Sales\Block\Adminhtml\Order\View as CoreView;
use Magento\Sales\Model\Order;

class View
{
    private OrderContext $context;

    public function __construct(OrderContext $context)
    {
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
        $payment = $view->getOrder()->getPayment();
        if (is_null($payment)) {
            return;
        }
        $status = $view->getOrder()->getStatus();
        if (str_starts_with($payment->getMethod(), 'fisrv_') && ($status === Order::STATE_COMPLETE || $status === Order::STATE_PROCESSING)) {
            $refundMessage = _('Do you want to refund this order?');
            $view->addButton(
                'refundaction',
                [
                    'label' => _('Refund'),
                    'class' => 'fiserv-refund-button',
                    'id' => 'fiserv-refund-button',
                    'onclick' => "confirmSetLocation('{$refundMessage}', '{$refundRoute}')"
                ]
            );
        }
        $view->addButton(
            'fiserv_checkout_button',
            [
                'label' => _('Fiserv Checkout Info'),
                'class' => 'fiserv-checkout-button',
                'id' => 'fiserv-checkout-button',
                'onclick' => 'showFiservCheckoutModal()'
            ]
        );
    }
}
