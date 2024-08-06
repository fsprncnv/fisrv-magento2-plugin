<?php

namespace Fisrv\Payment\Controller\Checkout;

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
        Session $session
    ) {
        $this->logger = $logger;
        $this->checkoutCreator = $checkoutCreator;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->session = $session;
        parent::__construct($context);
    }

    public function execute()
    {
        $checkoutUrl = $this->checkoutCreator->create();
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($checkoutUrl);

        return $resultRedirect;
    }
}