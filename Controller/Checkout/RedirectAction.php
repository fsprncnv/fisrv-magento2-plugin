<?php

namespace Fisrv\Payment\Controller\Checkout;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\App\Action\Action;
use Fisrv\Payment\Logger\DebugLogger;
use Magento\Framework\App\Action\Context;

class RedirectAction extends Action
{
    const CHECKOUT_LANE_BASEURL = 'https://ci.checkout-lane.com/#/?checkoutId=';

    private DebugLogger $logger;
    private CheckoutCreator $checkoutCreator;

    public function __construct(
        Context $context,
        Redirect $resultRedirectFactory,
        DebugLogger $logger,
        CheckoutCreator $checkoutCreator,
    ) {
        $this->logger = $logger;
        $this->checkoutCreator = $checkoutCreator;
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $checkoutId = $this->checkoutCreator->create();
        $this->logger->write('Processing checkout action...');

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl(self::CHECKOUT_LANE_BASEURL . $checkoutId);

        return $resultRedirect;
    }
}
