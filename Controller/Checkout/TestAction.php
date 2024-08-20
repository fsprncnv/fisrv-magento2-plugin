<?php

namespace Fisrv\Payment\Controller\Checkout;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;

class TestAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private OrderContext $context;

    public function __construct(
        OrderContext $context,
    ) {
        $this->context = $context;

        date_default_timezone_set('Europe/Berlin');
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
        echo date('m/d/Y h:i:s', time()) . ' TEST ROUTE<br/>';

        echo $this->context->getConfigData()->getCheckoutHost();

        return $this->context->getResponse();
    }
}
