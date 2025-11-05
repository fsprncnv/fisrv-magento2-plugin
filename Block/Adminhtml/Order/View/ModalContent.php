<?php
/**
 * @category    Fiserv
 * @package     Fiserv_Checkout
 */
namespace Fiserv\Checkout\Block\Adminhtml\Order\View;

use Exception;
use Fiserv\Checkout\Controller\Checkout\CheckoutCreator;
use Fiserv\Checkout\Controller\Checkout\OrderContext;
use Fisrv\Models\GetCheckoutIdResponse;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Throwable;

class ModalContent extends Template
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    private CheckoutCreator $checkoutCreator;
    private OrderContext $orderContext;

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        CheckoutCreator $checkoutCreator,
        OrderContext $orderContext,
        array $data = []
    ) {
        $this->orderContext = $orderContext;
        $this->orderRepository = $orderRepository;
        $this->checkoutCreator = $checkoutCreator;
        parent::__construct($context, $data);
    }

    /**
     * Get order ID from request
     *
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->getRequest()->getParam('order_id');
    }

    private function renderFiservCheckoutDetails(GetCheckoutIdResponse $checkout): array
    {
        $keysToKeep = ['orderId', 'traceId', 'storeId', 'paymentMethodUsed', 'checkoutId', 'transactionType', 'transactionStatus'];
        $filtered = array_filter(
            (array) $checkout,
            function ($value, $key) use ($keysToKeep): bool {
                return in_array($key, $keysToKeep);
            },
            ARRAY_FILTER_USE_BOTH
        );
        array_walk(
            $filtered,
            function (&$value, $key) {
                if (is_string($value)) {
                    return;
                }
                switch (get_class($value)) {
                    case 'Fisrv\Models\TransactionType':
                    case 'Fisrv\Models\TransactionStatus':
                        $value = $value->name;
                        break;
                    case 'Fisrv\Models\PaymentMethodUsed':
                        $value = $value->paymentMethodType;
                        break;
                    default:
                        $value = __("Not Available");
                        break;
                }
            },
        );
        return $filtered;
    }

    /**
     * Get order data
     *
     * @return array
     */
    public function getOrderData()
    {
        $orderId = $this->getOrderId();
        $data = [];
        if ($orderId) {
            try {
                $order = $this->orderRepository->get($orderId);
                $checkoutId = $order->getExtOrderId();
                $fiservData = [];
                try {
                    $checkoutDetails = $this->checkoutCreator->getCheckoutDetails($checkoutId);
                    $fiservData = $this->renderFiservCheckoutDetails($checkoutDetails);
                } catch (Throwable $error) {
                    $this->orderContext->getLogger()->write("Failed to fetch Fiserv checkout data: " . $error);
                }
                $this->orderContext->getLogger()->write($fiservData);

                $data = array_merge([
                    'increment_id' => $order->getIncrementId(),
                    'customer_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                    'status' => $order->getStatusLabel(),
                    'payment_method' => $order->getPayment() ? $order->getPayment()->getMethodInstance()->getTitle() : __('N/A'),
                    'created_at' => $this->_localeDate->formatDateTime(
                        new \DateTime($order->getCreatedAt()),
                        \IntlDateFormatter::MEDIUM,
                        \IntlDateFormatter::MEDIUM
                    )
                ], $fiservData);
            } catch (Exception $error) {
                $this->orderContext->getLogger()->write("Failed to prepare Magento order data: " . $error);
            }
        }
        return $data;
    }
}