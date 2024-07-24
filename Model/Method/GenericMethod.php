<?php

declare(strict_types=1);

namespace Fisrv\Payment\Model\Method;

use Magento\Framework\Url;
use Magento\Payment\Gateway\Config\ValueHandlerPool;
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
    }

    public function capture(InfoInterface $payment, $amount)
    {
        return $this;
    }

    public function acceptPayment(InfoInterface $payment)
    {
        echo 'accepting...';
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
