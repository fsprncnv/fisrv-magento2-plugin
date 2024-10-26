<?php

namespace Fiserv\Checkout\Controller\Adminhtml\Checkout;

use Exception;
use Fisrv\Exception\ErrorResponse;
use Fiserv\Checkout\Controller\Checkout\CheckoutCreator;
use Fiserv\Checkout\Controller\Checkout\OrderContext;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\RefundInvoiceInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;

/**
 * GET rest route to handle refund action
 */
class DetailsAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private CheckoutCreator $checkoutCreator;

    private OrderContext $context;

    private RefundInvoiceInterface $refundOrder;

    /**
     * RefundAction constructor.
     * Transaction return is handled via CheckoutCreator.
     * Store backend refund registration is handled via RefundInvoiceInterface.
     */
    public function __construct(
        CheckoutCreator $checkoutCreator,
        OrderContext $context,
        RefundInvoiceInterface $refundOrder
    ) {
        $this->checkoutCreator = $checkoutCreator;
        $this->context = $context;
        $this->refundOrder = $refundOrder;
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
        return $this->context->_redirect(
            'sales/order/view/',
            [
            'order_id' => $orderId
            ]
        );
    }
}
