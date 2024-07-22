<?php

namespace Fisrv\Payment\Controller\Checkout;

use Fisrv\Models\TransactionStatus;
use Fisrv\Models\WebhookEvent\WebhookEvent;
use Fisrv\Payment\Logger\DebugLogger;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

if (file_exists(__DIR__ . "/../../vendor/fisrv/php-client/vendor/autoload.php")) {
    require_once __DIR__ . "/../../vendor/fisrv/php-client/vendor/autoload.php";
}

class Webhook implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private Response $response;
    private Request $request;
    private DebugLogger $logger;
    private JsonFactory $jsonResultFactory;
    private OrderRepository $orderRepository;

    public function __construct(
        Response $response,
        Request $request,
        DebugLogger $logger,
        JsonFactory $jsonResultFactory,
        OrderRepository $orderRepository,
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->orderRepository = $orderRepository;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * Execute action based on request and return result
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $result->setHeader('Content-Type', 'application/json', true);

        $content = $this->request->getContent();
        $event = new WebhookEvent($content);
        $orderId = $this->request->getParam('orderid', false);

        if (!$orderId) {
            throw new \Exception('Order ID is invalid, cancelling process');
        }

        try {
            $checkoutId = $event->checkoutId;
        } catch (\Throwable $th) {
            throw new \Exception('Webhook event is invalid, cancelling process');
        }

        $this->logger->write('Webhook event for checkout ID ' . $checkoutId);
        $this->updateOrder(intval($orderId), $event);

        return $result->setData($event);
    }

    /**
     * Update magento order according to webhook event
     * 
     * @param \Fisrv\Models\WebhookEvent\WebhookEvent $event
     * @return void
     */
    private function updateOrder(int $orderId, WebhookEvent $event)
    {
        $order = $this->orderRepository->get($orderId);
        $status = Order::STATE_PROCESSING;

        switch ($event->transactionStatus) {
            case TransactionStatus::WAITING:
                $status = Order::STATE_NEW;
                break;
            case TransactionStatus::PARTIAL:
                $status = Order::STATE_PROCESSING;
                break;
            case TransactionStatus::APPROVED:
                $status = Order::STATE_COMPLETE;
                break;
            case TransactionStatus::PROCESSING_FAILED:
            case TransactionStatus::VALIDATION_FAILED:
            case TransactionStatus::DECLINED:
                $status = Order::STATE_CANCELED;
                break;
            default:
                $status = 'INVALID';
                break;
        }

        if ($status === 'INVALID') {
            return;
        }

        $order->setStatus($status)->setState($status);
        $this->orderRepository->save($order);
    }
}