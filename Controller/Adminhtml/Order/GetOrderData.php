<?php
/**
 * @category    Fiserv
 * @package     Fiserv_Checkout
 */
namespace Fiserv\Checkout\Controller\Adminhtml\Order;

use Fiserv\Checkout\Controller\Checkout\OrderContext;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class GetOrderData extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderRepositoryInterface $orderRepository,
        OrderContext $orderContext,
    ) {
        $orderContext->getLogger()->write('Calling from GetOrderData');

        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $orderId = $this->getRequest()->getParam('order_id');

        if (!$orderId) {
            return $result->setData(['error' => true, 'message' => __('No order ID provided')]);
        }

        try {
            $order = $this->orderRepository->get($orderId);

            $data = [
                'increment_id' => $order->getIncrementId(),
                'customer_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                'total' => $order->getGrandTotal(),
                'currency' => $order->getOrderCurrencyCode(),
                'status' => $order->getStatusLabel(),
                'payment_method' => $order->getPayment() ? $order->getPayment()->getMethodInstance()->getTitle() : 'N/A',
                'created_at' => $order->getCreatedAt()
            ];

            return $result->setData(['success' => true, 'data' => $data]);
        } catch (NoSuchEntityException $e) {
            return $result->setData(['error' => true, 'message' => __('Order not found')]);
        } catch (\Exception $e) {
            return $result->setData(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Sales::sales_order');
    }
}
