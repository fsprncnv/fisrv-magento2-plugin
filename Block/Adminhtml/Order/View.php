<?php

declare(strict_types=1);

namespace Fiserv\Checkout\Block\Adminhtml\Order;

use BackedEnum;
use Fiserv\Checkout\Controller\Checkout\CheckoutCreator;
use Fiserv\Checkout\Controller\Checkout\OrderContext;
use Fisrv\Models\GetCheckoutIdResponse;
use Fisrv\Models\TransactionType;
use Magento\Sales\Block\Adminhtml\Order\View as CoreView;
use Magento\Sales\Model\Order;

class View
{
    private OrderContext $context;
    private CheckoutCreator $checkoutCreator;

    public function __construct(
        OrderContext $context,
        CheckoutCreator $checkoutCreator
    ) {
        $this->context = $context;
        $this->checkoutCreator = $checkoutCreator;
    }

    private function renderCheckoutDetails(GetCheckoutIdResponse $checkout): string
    {
        $template = "<strong>Fiserv Checkout Details</strong>";
        foreach ($checkout as $key => $value) {
            if (!in_array($key, ['orderId', 'traceId', 'storeId', 'checkoutId', 'transactionType', 'transactionStatus'])) {
                continue;
            }
            if ($value instanceof BackedEnum) {
                $value = $value->name;
            }
            $template .= "<div><strong>" . ucwords($key) . ": </strong>$value</div>";
        }
        return $template;
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

        if (
            !str_starts_with($payment->getMethod(), 'fisrv_')
            || $view->getOrder()->getStatus() !== Order::STATE_COMPLETE
        ) {
            return;
        }

        $refundMessage = _('Do you want to refund this order?');
        $view->addButton(
            'refundaction',
            [
                'label' => _('Refund'),
                'onclick' => "confirmSetLocation('{$refundMessage}', '{$refundRoute}')"
            ]
        );


        $checkoutId = $view->getOrder()->getExtOrderId();
        $checkoutDetails = $this->checkoutCreator->getCheckoutDetails($checkoutId);
        $message = $this->renderCheckoutDetails($checkoutDetails);
        $message =
            $view->addButton(
                'detailsaction',
                [
                    'label' => _('Checkout Details'),
                    'onclick' => "confirmSetLocation('{$message}', '#')"
                ]
            );
    }


}
