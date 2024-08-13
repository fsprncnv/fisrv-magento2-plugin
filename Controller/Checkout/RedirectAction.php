<?php

namespace Fisrv\Payment\Controller\Checkout;

use Fisrv\Exception\ServerException;
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

    // @todo Update php client to parse exception fields
    private function getExceptionDetail($string)
    {
        $pos = strpos($string, '{');
        if (!$pos) {
            return false;
        }

        $parsed = json_decode(substr($string, $pos), true);

        if ($parsed['title'] === 'Authentication error') {
            return _('Your Fiserv API Credentials are invalid. Please reconfigure them.');
        }

        return 'Fiserv Server Error (' . $parsed['errors'][0]['title'] . '): ' . $parsed['errors'][0]['detail'];
    }

    public function execute()
    {
        $order = $this->context->getSession()->getLastRealOrder();
        $this->context->getLogger()->write('### START ORDER FLOW OF ORDER: ' . $order->getId() . ' ###');

        try {
            $checkoutUrl = $this->checkoutCreator->create($order);
        } catch (\Throwable $th) {

            if ($th instanceof ServerException) {
                $message = $this->getExceptionDetail($th->getMessage());
            }

            $this->context->messageManager->addErrorMessage($message ?? $th->getMessage());
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