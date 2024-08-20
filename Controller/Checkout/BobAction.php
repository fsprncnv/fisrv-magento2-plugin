<?php

namespace Fisrv\Payment\Controller\Checkout;

use Fisrv\Payment\Model\ConfigProvider;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;

class BobAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private OrderContext $context;
    private ConfigProvider $configProvider;

    public function __construct(
        OrderContext $context,
        ConfigProvider $configProvider
    ) {
        $this->context = $context;
        $this->configProvider = $configProvider;

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
        echo date('m/d/Y h:i:s', time()) . ' BOB ROUTE<br/>';
        $this->context->getConfigData()->setCheckoutHost('WORKING VALUE');

        return $this->context->getResponse();
    }
}