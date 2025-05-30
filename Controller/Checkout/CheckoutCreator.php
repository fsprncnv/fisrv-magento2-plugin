<?php

namespace Fiserv\Checkout\Controller\Checkout;

use Exception;
use Fisrv\Checkout\CheckoutClient;
use Fisrv\Models\CheckoutClientRequest;
use Fisrv\Models\Currency;
use Fisrv\Models\GetCheckoutIdResponse;
use Fisrv\Models\LineItem;
use Fisrv\Models\Locale;
use Fisrv\Models\PaymentsClientRequest;
use Fisrv\Models\PaymentsClientResponse;
use Fisrv\Models\PreSelectedPaymentMethod;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Locale\Resolver;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;

/**
 * Creates instance (checkout ID or URL) of hosted payment page.
 * Middleware for Fiserv client related processes.
 */
class CheckoutCreator
{
    private static CheckoutClient $client;

    private Store $store;

    private StoreManagerInterface $storeManager;

    private Resolver $resolver;

    private OrderRepository $orderRepository;

    private OrderContext $context;

    public function __construct(
        Store $store,
        StoreManagerInterface $storeManager,
        Resolver $resolver,
        OrderRepository $orderRepository,
        OrderContext $context,
    ) {
        $this->store = $store;
        $this->resolver = $resolver;
        $this->orderRepository = $orderRepository;
        $this->context = $context;

        set_error_handler([$this, 'noticeErrorHandler'], E_NOTICE | E_STRICT);
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

        /**
         * Set (preselected) payment method
         */
        try {
            $payment = $order->getPayment();

            if (is_null($payment)) {
                throw new Exception('Payment could not be retrieved from order');
            }

            $method = $payment->getMethod();
            $selectedMethod = self::PAYMENT_METHOD_MAP[$method];
            $request->checkoutSettings->preSelectedPaymentMethod = $selectedMethod;

            $this->context->getLogger()->write('Customer selected payment method: ' . $method);
        } catch (\Throwable $th) {
            $this->context->getLogger()->write('Creating generic checkout.');
        }
        $this->context->getLogger()->write('Mapping data from web shopt to API...');
        $request = self::transferBaseData($request, $order);
        $request = self::transferCartItems($request, $order);
        $request = self::transferAccountPerson($request, $order);

        $this->context->getLogger()->write('Creating checkout URL...');
        $this->context->getLogger()->write($request->__toString());
        $response = self::$client->createCheckout($request);

        $this->context->getLogger()->write('Retrieving response fields...');
        $checkoutId = $response->checkout->checkoutId;
        $traceId = $response->traceId;
        $checkoutLink = $response->checkout->redirectionUrl;
        $this->context->getConfigData()->setCheckoutHost($checkoutLink);

        $order->addCommentToStatusHistory(
            _("Fiserv checkout link $checkoutLink created with checkout ID $checkoutId and trace ID $traceId")
        );

        $order->setExtOrderId($checkoutId);
        $this->orderRepository->save($order);

        return $checkoutLink;
    }

    /**
     * Initialize request client
     */
    private function initClient(): void
    {
        $magentoStoreId = $this->store->getId();
        $moduleVersion = $this->context->getConfigData()->getModuleVersion();

        self::$client = new CheckoutClient(
            [
                'user' => 'Magento2Plugin/' . $moduleVersion,
                'is_prod' => $this->context->getConfigData()->isProductionMode($magentoStoreId),
                'api_key' => $this->context->getConfigData()->getApiKey($magentoStoreId),
                'api_secret' => $this->context->getConfigData()->getApiSecret($magentoStoreId),
                'store_id' => $this->context->getConfigData()->getFisrvStoreId($magentoStoreId),
            ]
        );
    }

    /**
     * Refund checkout
     *
     * @param  Order|OrderInterface $order Order to be refunded
     * @return PaymentsClientResponse Client response data
     */
    public function refundCheckout(Order|OrderInterface $order): PaymentsClientResponse
    {
        $this->initClient();

        if (is_null($order->getExtOrderId())) {
            throw new Exception('Refund failed. Order had no valid Magento ref ID.');
        }

        $this->context->getLogger()->write('External checkout ID: ' . $order->getExtOrderId());

        return self::$client->refundCheckout(
            new PaymentsClientRequest(
                [
                    'transactionAmount' => [
                        'total' => floatval($order->getGrandTotal()),
                        'currency' => $this->store->getBaseCurrencyCode()
                    ],
                ]
            ),
            $order->getExtOrderId()
        );
    }

    /**
     * Pass checkout data (totals, redirects, language etc.) to request object of checkout
     *
     * @param  \Fisrv\Models\CheckoutClientRequest $request
     * @param  \Magento\Sales\Model\Order          $order
     * @return \Fisrv\Models\CheckoutClientRequest
     */
    private function transferBaseData(CheckoutClientRequest $request, Order $order): CheckoutClientRequest
    {
        /**
         * Locale
         */
        $request->checkoutSettings->locale = Locale::tryFrom($this->resolver->getLocale()) ?? Locale::en_GB;
        $this->context->getLogger()->write($this->resolver->getLocale());

        /**
         * Currency
         */
        $request->transactionAmount->currency = Currency::tryFrom($this->store->getBaseCurrencyCode()) ?? Currency::EUR;

        /**
         * Order numbers, IDs
         */
        $request->order->orderDetails->purchaseOrderNumber = strval($order->getIncrementId());

        if ($order->getCustomerId() !== null) {
            $request->order->orderDetails->customerId = strval($order->getCustomerId());
        }

        /**
         * Order totals
         */
        $request->transactionAmount->total = floatval($order->getGrandTotal());
        $request->transactionAmount->components->subtotal = floatval($order->getSubtotal());
        $request->transactionAmount->components->vatAmount = floatval($order->getBaseTaxAmount());
        $request->transactionAmount->components->shipping = floatval($order->getShippingAmount());

        /**
         * Redirect URLs
         */
        $FORCE_SUCCESS_REDIRECT = false;

        $completeOrderUrl = $this->context->getUrl(
            'completeorder',
            true,
            [
                'order_id' => $order->getId(),
                '_nonce' => base64_encode($this->context->createSignature($order)),
                '_secure' => 'true'
            ]
        );

        $request->checkoutSettings->redirectBackUrls->successUrl = $completeOrderUrl;

        $request->checkoutSettings->redirectBackUrls->failureUrl = $FORCE_SUCCESS_REDIRECT ? $completeOrderUrl : $this->context->getUrl(
            'cancelorder',
            true,
            [
                'order_id' => $order->getId(),
            ]
        );

        /**
         * Append ampersand to allow checkout solution to append query params
         */
        $request->checkoutSettings->redirectBackUrls->failureUrl .= '&';

        /**
         * Webhook consumer route
         */
        $request->checkoutSettings->webHooksUrl = $this->context->getUrl(
            'webhook',
            true,
            [
                'order_id' => $order->getId(),
                '_nonce' => base64_encode($this->context->createSignature($order)),
                '_secure' => 'true',
            ]
        );

        return $request;
    }

    /**
     * Pass cart (line) items to checkout
     *
     * @param  \Fisrv\Models\CheckoutClientRequest $request
     * @param  \Magento\Sales\Model\Order          $order
     * @return \Fisrv\Models\CheckoutClientRequest
     */
    private function transferCartItems(CheckoutClientRequest $request, Order $order): CheckoutClientRequest
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        $items = $order->getItems();

        foreach ($items as $item) {
            try {
                $request->order->basket->lineItems[] = new LineItem(
                    [
                        'itemIdentifier' => strval($item->getItemId()),
                        'name' => $item->getName(),
                        'quantity' => intval($item->getQtyOrdered()),
                        'price' => $item->getPrice(),
                        'total' => $item->getPrice(),
                        'shippingCost' => 0,
                        'valueAddedTax' => 0,
                        'miscellaneousFee' => 0,
                    ]
                );
            } catch (\Throwable $th) {
                $this->context->getLogger()->write('Could not transfer cart item: ' . $item->getName() . ' ' . $th->getMessage());
            }
        }

        return $request;
    }

    /**
     * Pass acount information like billing data to checkout
     *
     * @param  \Fisrv\Models\CheckoutClientRequest $request
     * @param  \Magento\Sales\Model\Order          $order
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

    public function noticeErrorHandler($code, $err_msg, $err_file, $err_line, array $err_context)
    {
        $error = $log = null;
        switch ($code) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $error = 'Notice';
                break;
            case E_STRICT:
                $error = 'Strict';
                break;
        }

        $this->context->getLogger()->write($error . ": " . $err_msg . " in " . $err_file . " on line " . $err_line);
    }

    public function getCheckoutDetails(string $checkoutId): ?GetCheckoutIdResponse
    {
        $this->initClient();
        return self::$client->getCheckoutById($checkoutId);
    }
}