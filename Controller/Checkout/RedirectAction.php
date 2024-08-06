<?php

namespace Fisrv\Payment\Controller\Checkout;

use Exception;
use Fisrv\Exception\ServerException;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\App\Action\Action;
use Fisrv\Payment\Logger\DebugLogger;
use Magento\Framework\App\Action\Context;

class RedirectAction extends Action
{
    private DebugLogger $logger;
    private CheckoutCreator $checkoutCreator;
    private Session $session;

    public function __construct(
        Context $context,
        Redirect $resultRedirectFactory,
        DebugLogger $logger,
        CheckoutCreator $checkoutCreator,
        Session $session,
    ) {
        $this->logger = $logger;
        $this->checkoutCreator = $checkoutCreator;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->session = $session;
        parent::__construct($context);
    }

    // @todo Update php client to parse exception fields
    private function getExceptionDetail($string)
    {
        $pos = strpos($string, '{');
        if (!$pos) {
            return false;
        }

        $parsed = json_decode(substr($string, $pos), true);
        return 'Fiserv Server Error (' . $parsed['errors'][0]['title'] . '): ' . $parsed['errors'][0]['detail'];
    }

    public function execute()
    {
        try {
            $checkoutUrl = $this->checkoutCreator->create();
        } catch (\Throwable $th) {

            if ($th instanceof ServerException) {
                $message = $this->getExceptionDetail($th->getMessage());
            }

            $this->messageManager->addErrorMessage($message ?? $th->getMessage());
            return $this->_redirect('checkout/cart', [
                '_secure=true',
                'cancelled=true',
            ]);
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($checkoutUrl);
        return $resultRedirect;
    }
}