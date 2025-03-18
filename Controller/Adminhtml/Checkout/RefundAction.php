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
class RefundAction implements HttpGetActionInterface, CsrfAwareActionInterface
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

    /**
     * Refund transaction on payment gateway. Transaction that was created by Fiserv Checkout
     * is returned.
     *
     * @param  Order $order Order to be refundend
     * @throws Exception When payment method is not Fiserv or refund flow has failed (server-side)
     */
    private function refundOnGateway(Order $order): void
    {
        $payment = $order->getPayment();
        if (is_null($payment)) {
            return;
        }
        $method = $payment->getMethod();
        if (!str_starts_with($method, 'fisrv_')) {
            $this->context->getLogger()->write($method);
            throw new Exception(_('Payment was not provided by Fiserv'));
        }
        $response = null;
        try {
            $response = $this->checkoutCreator->refundCheckout($order);
        } catch (ErrorResponse $th) {
            $this->context->getLogger()->write('Refund failed server-side: ' . $th->getMessage());
            if (!is_null($response)) {
                $this->context->getLogger()->write((string) $th->response);
                throw new Exception(__('Refund has failed. Contact support with trace ID: %s and client ID %s.', $response->traceId, $response->clientRequestId));
            }
            throw new Exception(_('Payment was not provided by Fiserv'));
        }
        $this->context->getLogger()->write('Refund completed on Fiserv Gateway');
        $order->addCommentToStatusHistory(__('Fiserv transaction of ID ' . $response->ipgTransactionId . ' has been refunded with amount ' . number_format((float) $response->approvedAmount->total, 2, '.', '') . ' ' . $response->approvedAmount->currency->value));
    }

    /**
     * Refund order on magento backend. This is used for magento backend to properly register the
     * order refund and make it reviewable on store front.
     * Does not actually refund the Fiserv transaction.
     *
     * @param  Order $order Order to be refundend
     * @throws Exception When order is not invoiceable and thus not refundable. This happens if order state was not
     * properly configured
     */
    private function refundOnBackend(Order $order): void
    {
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        if (!$invoice instanceof Invoice) {
            throw new Exception(_('Invoice is invalid'));
        }
        $this->refundOrder->execute($invoice->getId(), [], true);
        $this->context->getLogger()->write('Refund completed on Magento Backend');
    }

    /**
     * Take order ID from query and process refund.
     *
     * {@inheritDoc}
     */
    public function execute()
    {
        $orderId = $this->context->getRequest()->getParam('order_id');
        $order = $this->context->getOrderRepository()->get($orderId);
        if (!($order instanceof Order)) {
            throw new Exception('Order could not be parsed');
        }
        try {
            $this->refundOnGateway($order);
            $this->refundOnBackend($order);
            $this->context->messageManager->addSuccessMessage('Order refunded');
        } catch (\Throwable $th) {
            $this->context->messageManager->addErrorMessage($th->getMessage());
        }
        return $this->context->_redirect(
            'sales/order/view/',
            [
                'order_id' => $orderId
            ]
        );
    }
}
