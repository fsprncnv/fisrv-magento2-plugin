<?php

namespace Fisrv\Payment\Observer;

use Fisrv\Payment\Controller\Checkout\RedirectAction;
use Fisrv\Payment\Logger\DebugLogger;
use Fisrv\Payment\Model\Method\GenericMethod;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ActionFlag;

if (file_exists(__DIR__ . "/../vendor/fisrv/php-client/vendor/autoload.php")) {
    require_once __DIR__ . "/../vendor/fisrv/php-client/vendor/autoload.php";
}

class OrderObserver implements ObserverInterface
{
    private DebugLogger $logger;
    private RedirectAction $redirect;

    private UrlInterface $url;
    private Http $http;

    private static \Fisrv\Checkout\CheckoutClient $client;
    private GenericMethod $genericMethod;

    private ActionFlag $actionFlag;

    public function __construct(
        DebugLogger $logger,
        RedirectAction $redirect,
        UrlInterface $url,
        Http $http,
        GenericMethod $genericMethod,
        ActionFlag $actionFlag,
    ) {
        $this->redirect = $redirect;
        $this->logger = $logger;
        $this->url = $url;
        $this->http = $http;
        $this->genericMethod = $genericMethod;
        $this->actionFlag = $actionFlag;
    }

    public function execute(Observer $observer)
    {
        // $event = $observer->getEvent();
        // $order = $event->getData('order');

        // if ($order->getPayment()->getMethod() !== $this->genericMethod->getCode()) {
        //     $this->logger->write('Cancelling since method is incorrect');
        //     return;
        // }

        $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);

        $checkoutId = 'xxx';
        // $checkoutId = self::createCheckout();
        // $this->logger->write('Created checkout ID: ' . $checkoutId);

        $this->logger->write('GenericObserver execute.');
        $target = $this->url->getUrl('fisrv/checkout/redirectaction', [
            'checkoutId' => $checkoutId
        ]);

        $this->http->setRedirect($target)->sendResponse();

        return $this;
    }
}
