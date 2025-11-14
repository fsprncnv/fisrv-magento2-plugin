<?php

namespace Fiserv\Checkout\Controller\Checkout;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;

class StatusAction implements HttpGetActionInterface, CsrfAwareActionInterface
{
    private OrderContext $context;

    private FiservApiService $fiservApiService;

    public function __construct(
        OrderContext $context,
        FiservApiService $fiservApiService,
    ) {
        $this->context = $context;
        $this->fiservApiService = $fiservApiService;
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
        $this->context->getLogger()->write('Starting API health check');
        ob_start();
        $status = __("You're all set!");
        $defaultFailureStatus = __('Something went wrong');
        try {
            $report = $this->fiservApiService->reportHealthCheck();
            $this->context->getLogger()->write('API health check was successful: ' . json_encode($report));
            if ($report->httpCode != 200) {
                $status = $report->error->message;
                $this->context->getLogger()->write('API health check reported following error response: ' . json_encode($report));
            }
        } catch (\Throwable $th) {
            $this->context->getLogger()->write('API health check had following failure: ' . json_encode($report));
            $bufferCapture = ob_get_contents();
            $rawError = $this->getStringAfterWord($bufferCapture, 'JSON content:');
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
