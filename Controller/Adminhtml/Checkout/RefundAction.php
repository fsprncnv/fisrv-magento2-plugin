<?php

namespace Fisrv\Payment\Controller\Adminhtml\Checkout;

use Exception;
use Fisrv\Payment\Controller\Checkout\CheckoutCreator;
use Fisrv\Payment\Controller\Checkout\OrderContext;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\RefundInvoiceInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;

class RefundAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private CheckoutCreator $checkoutCreator;
    private OrderContext $context;
    private RefundInvoiceInterface $refundOrder;

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

    private function refundOnGateway(Order $order): void
    {
        $method = $order->getPayment()->getMethod();

        if (!str_starts_with($method, 'fisrv_')) {
            throw new Exception(_('Method is not Fisrv. Cancelling refund process.'));
        }

        $response = $this->checkoutCreator->refundCheckout($order);

        if (!isset($response->approvedAmount)) {
            $this->context->getLogger()->write('Refund failed on server-side:');
            $this->context->getLogger()->write((string) $response);
            throw new Exception(_('Refund has failed.'));
        }

        $order->addStatusToHistory('Fisrv transaction of ID ' . $response->ipgTransactionId . ' has been refunded with amount ' . $response->approvedAmount->total);
    }

    private function refundOnBackend(Order $order): void
    {
        $invoice = $order->getInvoiceCollection()->getFirstItem();

        if (!$invoice instanceof Invoice) {
            throw new Exception('Invoice is not valid.');
        }

        $this->refundOrder->execute($invoice->getId(), [], true);
    }

    public function execute()
    {
        $orderId = $this->context->getRequest()->getParam('order_id');
        $order = $this->context->getOrderRepository()->get($orderId);

        try {
            $this->refundOnGateway($order);
            $this->refundOnBackend($order);
        } catch (\Throwable $th) {
            $this->context->messageManager->addErrorMessage($th->getMessage());
        }

        $this->context->messageManager->addSuccessMessage('Order refunded');

        return $this->context->_redirect('sales/order/view/', [
            'order_id' => $orderId
        ]);
    }
}