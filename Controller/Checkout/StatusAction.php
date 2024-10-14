<?php

namespace Fiserv\Checkout\Controller\Checkout;

use Fisrv\Payments\PaymentsClient;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;

// if (file_exists(__DIR__ . '/../../vendor/fisrv/php-client/vendor/autoload.php')) {
//     include_once __DIR__ . '/../../vendor/fisrv/php-client/vendor/autoload.php';
// }

class StatusAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private OrderContext $context;
    private PaymentsClient $client;

    public function __construct(
        OrderContext $context,
    ) {
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
        $this->client = new PaymentsClient(
            [
                'is_prod' => $this->context->getConfigData()->isProductionMode(),
                'api_key' => $this->context->getConfigData()->getApiKey(),
                'api_secret' => $this->context->getConfigData()->getApiSecret(),
                'store_id' => $this->context->getConfigData()->getFisrvStoreId(),
            ]
        );

        $report = $this->client->reportHealthCheck();

        if ($report->httpCode != 200) {
            $status = $report->error->message;
            $this->context->getLogger()->write('API health check reported following error response: ' . json_encode($report));
        }

        $this->context->getResponse()->setContent(
            json_encode(
                [
                    'status' => $status ?? "You're all set!",
                    'code' => $report->httpCode
                ]
            )
        );

        return $this->context->getResponse();
    }
}