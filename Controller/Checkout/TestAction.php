<?php

namespace Fisrv\Payment\Controller\Checkout;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Action\Action;
use Fisrv\Payment\Logger\DebugLogger;
use Magento\Framework\App\Action\Context;

class TestAction extends Action
{
    private DebugLogger $logger;
    private Session $session;

    public function __construct(
        Context $context,
        DebugLogger $logger,
        Session $session
    ) {
        $this->logger = $logger;
        $this->session = $session;
        parent::__construct($context);
    }

    public function execute()
    {
        echo 'TEST ACTION';
        print_r($this->session->getUser());
    }
}