<?php

namespace Fisrv\Payment\Controller\Checkout;

use Fisrv\Checkout\CheckoutClient;
use Fisrv\Models\CheckoutClientRequest;
use Fisrv\Models\LineItem;
use Fisrv\Models\PaymentsClientRequest;
use Fisrv\Models\PaymentsClientResponse;
use Fisrv\Models\PreSelectedPaymentMethod;
use Fisrv\Models\Currency;
use Fisrv\Models\Locale;
use Magento\Sales\Model\Order;
use Magento\Framework\Locale\Resolver;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\Store;

if (file_exists(__DIR__ . "/../../vendor/fisrv/php-client/vendor/autoload.php")) {
    require_once __DIR__ . "/../../vendor/fisrv/php-client/vendor/autoload.php";
}

/**
 * Creates instance (checkout ID or URL) of hosted payment page.
 * Middleware for Fiserv client related processes.
 */
class CheckoutCreator
{
    private static CheckoutClient $client;
    private Store $store;
    private Resolver $resolver;
    private OrderRepository $orderRepository;
    private OrderContext $context;

    public function __construct(
        Store $store,
        Resolver $resolver,
        OrderRepository $orderRepository,
        OrderContext $context
    ) {
        $this->store = $store;
        $this->resolver = $resolver;
        $this->orderRepository = $orderRepository;
        $this->context = $context;
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
    public function create(Order $order): string
    {
        $this->initClient();
        $request = self::$client->createBasicCheckoutRequest(0, '', '');

        /** Set (preselected) payment method */
        try {
            $method = $order->getPayment()->getMethod();
            $selectedMethod = self::PAYMENT_METHOD_MAP[$method];
            $request->checkoutSettings->preSelectedPaymentMethod = $selectedMethod;

            $this->context->getLogger()->write('Preselected method is: ' . $method);
        } catch (\Throwable $th) {
            $this->context->getLogger()->write('Creating generic checkout.');
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

        $order->setExtOrderId($checkoutId);
        $this->orderRepository->save($order);

        return $checkoutLink;
    }

    /**
     * Initialize request client
     */
    private function initClient()
    {
        $magentoStoreId = $this->store->getId();
        $moduleVersion = $this->context->getConfigData()->getModuleVersion();

        self::$client = new CheckoutClient([
            'user' => 'Magento2Plugin/' . $moduleVersion,
            'is_prod' => $this->context->getConfigData()->isProductionMode($magentoStoreId),
            'api_key' => $this->context->getConfigData()->getApiKey($magentoStoreId),
            'api_secret' => $this->context->getConfigData()->getApiSecret($magentoStoreId),
            'store_id' => $this->context->getConfigData()->getFisrvStoreId($magentoStoreId),
        ]);
    }

    /**
     * Refund checkout
     *
     * @param Order $order Order to be refunded
     * @return PaymentsClientResponse Client response data
     */
    public function refundCheckout(Order $order): PaymentsClientResponse
    {
        $this->initClient();

        return self::$client->refundCheckout(new PaymentsClientRequest([
            'transactionAmount' => [
                'total' => floatval($order->getGrandTotal()),
                'currency' => $this->store->getBaseCurrencyCode()
            ],
        ]), $order->getExtOrderId());
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
        $request->checkoutSettings->redirectBackUrls->failureUrl = $this->context->getUrl('cancelorder', true, [
            'order_id' => $order->getId(),
        ]);
        $request->checkoutSettings->redirectBackUrls->successUrl = $this->context->getUrl('completeorder', true, [
            'order_id' => $order->getId(),
            '_nonce' => base64_encode($this->context->createSignature($order)),
            '_secure' => 'true'
        ]);

        /** Append ampersand to allow checkout solution to append query params */
        $request->checkoutSettings->redirectBackUrls->failureUrl .= '&';

        /** Webhook consumer route */
        $request->checkoutSettings->webHooksUrl = $this->context->getUrl('webhook', true, [
            'order_id' => $order->getId(),
            '_nonce' => base64_encode($this->context->createSignature($order)),
            '_secure' => 'true',
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
                $this->context->getLogger()->write('Could not transfer cart item: ' . $item->getName() . ' ' . $th->getMessage());
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
