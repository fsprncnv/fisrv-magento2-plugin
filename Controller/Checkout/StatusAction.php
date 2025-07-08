<?php

namespace Fiserv\Checkout\Controller\Checkout;

use Fisrv\Payments\PaymentsClient;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;

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

    private function getStringAfterWord($string, $word): string|null
    {
        $pos = strpos($string, $word);
        if ($pos === false) {
            return null;
        }
        return substr($string, $pos + strlen($word));
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
        ob_start();
        $status = "You're all set!";
        $defaultFailureStatus = "Something went wrong";
        try {
            $report = $this->client->reportHealthCheck();
            if ($report->httpCode != 200) {
                $status = $report->error->message;
                $this->context->getLogger()->write('API health check reported following error response: ' . json_encode($report));
            }
        } catch (\Throwable $th) {
            $bufferCapture = ob_get_contents();
            $rawError = $this->getStringAfterWord($bufferCapture, "JSON content:");
            if (is_null($rawError)) {
                $status = $defaultFailureStatus;
            } else {
                $parsedError = json_decode($rawError);
                $status = isset($parsedError->details[0]->message) ? $parsedError->details[0]->message : $defaultFailureStatus;
            }
        }
        $this->context->getResponse()->setContent(
            json_encode(
                [
                    'status' => $status,
                    'code' => $report->httpCode
                ]
            )
        );
        // Clean possible non-JSON response data (caused by PHP dumps like exception messages) 
        ob_end_clean();
        return $this->context->getResponse();
    }
}