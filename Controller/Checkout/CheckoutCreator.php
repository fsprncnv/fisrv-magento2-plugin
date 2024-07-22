<?php

namespace Fisrv\Payment\Controller\Checkout;

use Fisrv\Models\CheckoutClientRequest;
use Fisrv\Models\LineItem;
use Magento\Sales\Model\Order;
use Fisrv\Models\Currency;
use Fisrv\Models\Locale;
use Magento\Framework\Locale\Resolver;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\Store;

if (file_exists(__DIR__ . "/../../vendor/fisrv/php-client/vendor/autoload.php")) {
    require_once __DIR__ . "/../../vendor/fisrv/php-client/vendor/autoload.php";
}

class CheckoutCreator
{
    private static \Fisrv\Checkout\CheckoutClient $client;

    private Session $session;
    private Store $store;
    private Resolver $resolver;

    public function __construct(
        Session $session,
        Store $store,
        Resolver $resolver
    ) {
        $this->session = $session;
        $this->store = $store;
        $this->resolver = $resolver;
    }

    /**
     * Create a checkout link
     *
     * @return string Checkout ID of hosted payment page
     */
    public function create(): string
    {
        $order = $this->session->getLastRealOrder();

        self::$client = new \Fisrv\Checkout\CheckoutClient([
            'user' => 'Magento2Plugin/0.0.1',
            'is_prod' => false,
            'api_key' => '7V26q9EbRO2hCmpWARdFtOyrJ0A4cHEP',
            'api_secret' => 'KCFGSj3JHY8CLOLzszFGHmlYQ1qI9OSqNEOUj24xTa0',
            'store_id' => '72305408',
        ]);

        $request = self::$client->createBasicCheckoutRequest(43.99, 'https://success.com', 'https://failure.com');
        $request = self::transferBaseData($request, $order);
        $request = self::transferCartItems($request, $order);
        $request = self::transferAccountPerson($request, $order);

        $response = self::$client->createCheckout($request);

        return $response->checkout->checkoutId;
    }

    /**
     * Pass checkout data (totals, redirects, language etc.) to request object of checkout
     */
    private function transferBaseData(CheckoutClientRequest $req, Order $order): CheckoutClientRequest
    {
        /** Locale */
        $req->checkoutSettings->locale = Locale::tryFrom($this->resolver->getLocale()) ?? Locale::en_GB;

        /** Currency */
        $req->transactionAmount->currency = Currency::tryFrom($order->getOrderCurrency()->getCurrencyCode()) ?? Currency::EUR;

        /** Order numbers, IDs */

        /** Order totals */
        $req->transactionAmount->total = floatval($order->getGrandTotal());
        $req->transactionAmount->components->subtotal = floatval($order->getSubtotal());
        $req->transactionAmount->components->vatAmount = floatval($order->getBaseTaxAmount());
        $req->transactionAmount->components->shipping = floatval($order->getShippingAmount());

        /** Redirect URLs */
        $baseUrl = $this->store->getBaseUrl();
        $req->checkoutSettings->redirectBackUrls->successUrl = $baseUrl . '/checkout/onepage/success/?utm_nooverride=1';
        $req->checkoutSettings->redirectBackUrls->failureUrl = $baseUrl . '/checkout/onepage/success/?utm_nooverride=1';

        /** Append ampersand to allow checkout solution to append query params */
        $req->checkoutSettings->redirectBackUrls->failureUrl .= '&';

        $req->checkoutSettings->webHooksUrl = '';

        return $req;
    }

    private function transferCartItems(CheckoutClientRequest $req, Order $order): CheckoutClientRequest
    {
        $items = $order->getItems();

        foreach ($items as $item) {
            $req->order->basket->lineItems[] = new LineItem([
                'itemIdentifier' => strval($item->getItemId()),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'quantity' => intval($item->getQtyOrdered()),
                'total' => $item->getPriceInclTax(),
            ]);
        }

        return $req;
    }

    private function transferAccountPerson(CheckoutClientRequest $req, Order $order): CheckoutClientRequest
    {
        $req->order->billing->person->firstName = $order->getBillingAddress()->getFirstname();
        $req->order->billing->person->lastName = $order->getBillingAddress()->getLastname();
        $req->order->billing->contact->email = $order->getBillingAddress()->getEmail();
        $req->order->billing->address->address1 = $order->getBillingAddress()->getStreet()[0];
        $req->order->billing->address->city = $order->getBillingAddress()->getCity();
        $req->order->billing->address->country = $order->getBillingAddress()->getCountryId();
        $req->order->billing->address->postalCode = $order->getBillingAddress()->getPostcode();

        return $req;
    }

}