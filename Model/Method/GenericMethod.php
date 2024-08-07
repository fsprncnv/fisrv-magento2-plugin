<?php

declare(strict_types=1);

namespace Fisrv\Payment\Model\Method;

use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;

use Fisrv\Payment\Logger\DebugLogger;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Response\Http;

class GenericMethod extends Adapter
{
    private DebugLogger $debugLogger;
    private UrlInterface $url;
    private Http $http;
    private string $title;
    private ConfigData $configData;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Payment\Gateway\Config\ValueHandlerPoolInterface $valueHandlerPool,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        DebugLogger $debugLogger,
        UrlInterface $url,
        Http $http,
        string $title,
        ConfigData $configData
    ) {
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
        );

        $this->debugLogger = $debugLogger;
        $this->url = $url;
        $this->http = $http;
        $this->title = $title;
        $this->configData = $configData;
    }

    public function capture(InfoInterface $payment, $amount)
    {
        $this->debugLogger->write('--- Order from GenericMethod::capture ---');
        $this->debugLogger->write($payment);

        return $this;
    }

    public function isActive($storeId = null)
    {
        return $this->configData->isMethodActive($this->getCode()) ?? false;
    }

    public function acceptPayment(InfoInterface $payment)
    {
        $this->debugLogger->write('--- Order from GenericMethod::acceptPayment ---');
        $this->debugLogger->write($payment);

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function canRefund()
    {
        return true;
    }

    public function refund(InfoInterface $infoInterface, $amount)
    {
        $this->debugLogger->write('--- Refund from GenericMethod::refund ---');
    }
}