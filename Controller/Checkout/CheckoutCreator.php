<?php

namespace Fisrv\Payment\Controller\Checkout;

use Fisrv\Models\CheckoutClientRequest;
use Fisrv\Models\LineItem;
use Fisrv\Models\PreSelectedPaymentMethod;
use Fisrv\Payment\Logger\DebugLogger;
use Fisrv\Payment\Model\Method\ConfigData;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Fisrv\Models\Currency;
use Fisrv\Models\Locale;
use Magento\Framework\Locale\Resolver;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\Store;


if (file_exists(__DIR__ . "/../../vendor/fisrv/php-client/vendor/autoload.php")) {
    require_once __DIR__ . "/../../vendor/fisrv/php-client/vendor/autoload.php";
}

/**
 * Creates instance (checkout ID or URL) of hosted payment page
 */
class CheckoutCreator
{
    private static \Fisrv\Checkout\CheckoutClient $client;
    private Session $session;
    private Store $store;
    private Resolver $resolver;
    private DebugLogger $logger;
    private UrlInterface $url;
    private ConfigData $config;
    private OrderRepository $orderRepository;
    private UrlInterface $urlInterface;

    public function __construct(
        Session $session,
        Store $store,
        Resolver $resolver,
        DebugLogger $logger,
        UrlInterface $url,
        ConfigData $config,
        OrderRepository $orderRepository,
        UrlInterface $urlInterface
    ) {
        $this->session = $session;
        $this->store = $store;
        $this->resolver = $resolver;
        $this->logger = $logger;
        $this->url = $url;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->urlInterface = $urlInterface;
    }

    private const PAYMENT_METHOD_MAP = [
        'fisrv_creditcard' => PreSelectedPaymentMethod::CARDS,
        'fisrv_applepay' => PreSelectedPaymentMethod::APPLE,
        'fisrv_googlepay' => PreSelectedPaymentMethod::GOOGLEPAY,
    ];

    /**
     * Create a checkout link
     *
     * @return string Checkout ID of hosted payment page
     */
    public function create(): string
    {
        $order = $this->session->getLastRealOrder();
        $this->logger->write('--- Order from CheckoutCreator::getLastRealOrder ---');
        $this->logger->write($order);

        $magentoStoreId = $this->store->getId();
        $moduleVersion = $this->config->getModuleVersion();

        // self::$client = new \Fisrv\Checkout\CheckoutClient([
        //     'user' => 'Magento2Plugin/' . $moduleVersion,
        //     'is_prod' => !$this->config->isSandbox($magentoStoreId),
        //     'api_key' => $this->config->getApiKey($magentoStoreId),
        //     'api_secret' => $this->config->getApiSecret($magentoStoreId),
        //     'store_id' => $this->config->getFisrvStoreId($magentoStoreId),
        // ]);

        self::$client = new \Fisrv\Checkout\CheckoutClient([
            'user' => 'Magento2Plugin/' . $moduleVersion,
            'is_prod' => !$this->config->isSandbox($magentoStoreId),
            'api_key' => '7V26q9EbRO2hCmpWARdFtOyrJ0A4cHEP',
            'api_secret' => 'KCFGSj3JHY8CLOLzszFGHmlYQ1qI9OSqNEOUj24xTa0',
            'store_id' => '72305408',
        ]);

        $request = self::$client->createBasicCheckoutRequest(0, '', '');

        /** Set (preselected) payment method */
        try {
            $method = $this->session->getQuote()->getPayment()->getMethod();
            $selectedMethod = self::PAYMENT_METHOD_MAP[$method];
            $request->checkoutSettings->preSelectedPaymentMethod = $selectedMethod;

            $this->logger->write('Current method is: ' . $method);
            echo $method;
        } catch (\Throwable $th) {
            $this->logger->write('Creating generic checkout.');
        }

        $request = self::transferBaseData($request, $order);
        $request = self::transferCartItems($request, $order);
        $request = self::transferAccountPerson($request, $order);

        $response = self::$client->createCheckout($request);

        $checkoutId = $response->checkout->checkoutId;
        $checkoutLink = $response->checkout->redirectionUrl;
        $traceId = $response->traceId;

        $order->addCommentToStatusHistory(
            __(
                'Fisrv checkout link %1 created with checkout ID %2 and trace ID %3.',
                $checkoutLink,
                $checkoutId,
                $traceId,
            )
        );

        $this->orderRepository->save($order);

        return $checkoutLink;
    }


    /**
     * Pass checkout data (totals, redirects, language etc.) to request object of checkout
     * 
     * @param \Fisrv\Models\CheckoutClientRequest $request
     * @param \Magento\Sales\Model\Order $order
     * @return \Fisrv\Models\CheckoutClientRequest
     */
    private function transferBaseData(CheckoutClientRequest $request, Order $order): CheckoutClientRequest
    {
        /** Locale */
        $request->checkoutSettings->locale = Locale::tryFrom($this->resolver->getLocale()) ?? Locale::en_GB;

        /** Currency */
        $request->transactionAmount->currency = Currency::tryFrom($this->store->getBaseCurrencyCode()) ?? Currency::EUR;

        /** Order numbers, IDs */
        $request->merchantTransactionId = strval($order->getId());
        $request->order->orderDetails->purchaseOrderNumber = strval($order->getIncrementId());

        /** Order totals */
        $request->transactionAmount->total = floatval($order->getGrandTotal());
        $request->transactionAmount->components->subtotal = floatval($order->getSubtotal());
        $request->transactionAmount->components->vatAmount = floatval($order->getBaseTaxAmount());
        $request->transactionAmount->components->shipping = floatval($order->getShippingAmount());

        /** Redirect URLs */
        $request->checkoutSettings->redirectBackUrls->successUrl = $this->url->getUrl('fisrv/checkout/completeorder');
        $request->checkoutSettings->redirectBackUrls->failureUrl = $this->url->getUrl('fisrv/checkout/cancelorder');

        /** Append ampersand to allow checkout solution to append query params */
        $request->checkoutSettings->redirectBackUrls->failureUrl .= '&';

        $request->checkoutSettings->webHooksUrl = $this->url->getUrl('fisrv/checkout/webhook', [
            'orderid' => $order->getId()
        ]);

        return $request;
    }

    /**
     * Pass cart (line) items to checkout 
     * 
     * @param \Fisrv\Models\CheckoutClientRequest $request
     * @param \Magento\Sales\Model\Order $order
     * @return \Fisrv\Models\CheckoutClientRequest
     */
    private function transferCartItems(CheckoutClientRequest $request, Order $order): CheckoutClientRequest
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        $items = $order->getItems();

        foreach ($items as $item) {
            try {
                $request->order->basket->lineItems[] = new LineItem([
                    'itemIdentifier' => strval($item->getItemId()),
                    'name' => $item->getName(),
                    'quantity' => intval($item->getQtyOrdered()),
                    'price' => $item->getPrice(),
                    'total' => $item->getPrice(),
                    'shippingCost' => 0,
                    'valueAddedTax' => 0,
                    'miscellaneousFee' => 0,
                ]);
            } catch (\Throwable $th) {
                $this->logger->write('Could not transfer cart item: ' . $item->getName() . ' ' . $th->getMessage());
            }
        }

        return $request;
    }

    /**
     * Pass acount information like billing data to checkout
     * 
     * @param \Fisrv\Models\CheckoutClientRequest $request
     * @param \Magento\Sales\Model\Order $order
     * @return \Fisrv\Models\CheckoutClientRequest
     */
    private function transferAccountPerson(CheckoutClientRequest $request, Order $order): CheckoutClientRequest
    {
        if (is_null($order->getBillingAddress())) {
            return $request;
        }

        $request->order->billing->person->firstName = $order->getBillingAddress()->getFirstname();
        $request->order->billing->person->lastName = $order->getBillingAddress()->getLastname();
        $request->order->billing->contact->email = $order->getBillingAddress()->getEmail();
        $request->order->billing->address->address1 = $order->getBillingAddress()->getStreet()[0];
        $request->order->billing->address->city = $order->getBillingAddress()->getCity();
        $request->order->billing->address->country = $order->getBillingAddress()->getCountryId();
        $request->order->billing->address->postalCode = $order->getBillingAddress()->getPostcode();

        return $request;
    }

}