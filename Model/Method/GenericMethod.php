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

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Payment\Gateway\Config\ValueHandlerPoolInterface $valueHandlerPool,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        DebugLogger $debugLogger,
        UrlInterface $url,
        Http $http
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
    }

    public function capture(InfoInterface $payment, $amount)
    {
        // $checkoutId = 'abcdef';
        // $this->debugLogger->write('Capturing checkout: ' . $checkoutId);

        // $target = $this->url->getUrl('fisrv/checkout/redirectaction', [
        //     'checkoutId' => $checkoutId
        // ]);

        // $this->http->setRedirect($target)->sendResponse();

        return $this;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        echo 'authorizing...';
        return $this;
    }

    public function acceptPayment(InfoInterface $payment)
    {
        echo 'accepting...';
        return $this;
    }
}
