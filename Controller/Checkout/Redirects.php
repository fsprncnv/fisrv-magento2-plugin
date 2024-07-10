<?php

namespace Fisrv\Payment\Controller\Checkout;

if (file_exists(__DIR__ . "/../../Library/vendor/autoload.php")) {
    require_once __DIR__ . "/../../Library/vendor/autoload.php";
}

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Throwable;

class Redirects extends Action
{
    private static \Fisrv\Checkout\CheckoutClient $client;

    public function __construct(
        Context $context,
        Redirect $resultRedirectFactory
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    /**
     * Create a checkout link
     *
     * @return string URL of hosted payment page
     *
     * @throws Throwable Error thrown from fisrv SDK (Request Errors). Error is caught by setting
     * returned checkout link to '#' (no redirect)
     */
    public static function createCheckoutLink(): string
    {
        self::$client = new \Fisrv\Checkout\CheckoutClient([
            'user' => 'Magento2Plugin/0.0.1',
            'is_prod' => false,
            'api_key' => '7V26q9EbRO2hCmpWARdFtOyrJ0A4cHEP',
            'api_secret' => 'KCFGSj3JHY8CLOLzszFGHmlYQ1qI9OSqNEOUj24xTa0',
            'store_id' => '72305408',
        ]);

        $request = self::$client->createBasicCheckoutRequest(43.99, 'https://success.com', 'https://failure.com');
        $request = self::pass_checkout_data($request);

        $response = self::$client->createCheckout($request);
        $checkout_link = $response->checkout->redirectionUrl;

        return $checkout_link;
    }

    /**
     * Pass checkout data (totals, redirects, language etc.) to request object of checkout
     */
    private static function pass_checkout_data(\Fisrv\Models\CheckoutClientRequest $req): \Fisrv\Models\CheckoutClientRequest
    {
        /** Locale */

        /** Currency */

        /** order numbers, IDs */

        /** Order totals */
        // $req->transactionAmount->total = floatval($order->get_total());
        // $req->transactionAmount->components->subtotal = floatval($order->get_subtotal());
        // $req->transactionAmount->components->vatAmount = floatval($order->get_total_tax());
        // $req->transactionAmount->components->shipping = floatval($order->get_shipping_total());

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $baseUrl = $storeManager->getStore()->getBaseUrl();

        /** Redirect URLs */
        $req->checkoutSettings->redirectBackUrls->successUrl = $baseUrl . '/checkout/onepage/success/';
        $req->checkoutSettings->redirectBackUrls->failureUrl = $baseUrl . '/checkout/onepage/success/';

        /** Append ampersand to allow checkout solution to append query params */
        $req->checkoutSettings->redirectBackUrls->failureUrl .= '&';

        $req->checkoutSettings->webHooksUrl = '';

        return $req;
    }

    private function sendJsonResponse()
    {
        // $params = $this->getRequest()->getParams();
        // $resultJson = $this->resultJsonFactory->create();

        // echo 'executing some action...';
        // return $resultJson->setData([
        //     'messages' => 'Success. Params: ' . json_encode($params),
        //     'error' => false
        // ]);
    }

    public function execute()
    {
        echo 'Redirecting...';

        $checkoutUrl = self::createCheckoutLink();

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($checkoutUrl);
        return $resultRedirect;
    }
}
