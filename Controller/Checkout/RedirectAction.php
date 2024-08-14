<?php

namespace Fisrv\Payment\Controller\Checkout;

use Fisrv\Exception\ErrorResponse;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;

/**
 * GET rest route which is triggered on initial Place Order button press.
 * Redirects to external page.
 */
class RedirectAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private CheckoutCreator $checkoutCreator;
    private OrderContext $context;

    public function __construct(
        CheckoutCreator $checkoutCreator,
        OrderContext $context
    ) {
        $this->checkoutCreator = $checkoutCreator;
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

    public function execute()
    {
        $order = $this->context->getSession()->getLastRealOrder();

        try {
            $checkoutUrl = $this->checkoutCreator->create($order);
        } catch (\Throwable $th) {
            if ($th instanceof ErrorResponse) {
                $this->context->messageManager->addErrorMessage($th->getMessage());
            }

            return $this->context->_redirect('checkout/cart', [
                '_query' => [
                    '_secure' => 'true',
                    'order_cancelled' => 'true'
                ]
            ]);
        }

        $resultRedirect = $this->context->resultRedirectFactory->create();
        $resultRedirect->setUrl($checkoutUrl);
        return $resultRedirect;
    }
}