<?php

namespace Fiserv\Checkout\Controller\Checkout;

use Exception;
use Fisrv\Models\TransactionStatus;
use Fisrv\Models\WebhookEvent\WebhookEvent;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;

if (file_exists(__DIR__ . '/../../vendor/fisrv/php-client/vendor/autoload.php')) {
    include_once __DIR__ . '/../../vendor/fisrv/php-client/vendor/autoload.php';
}

/**
 * POST rest route.
 * Handling consumption of webhook events.
 */
class Webhook implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private OrderContext $context;

    private JsonFactory $jsonResultFactory;

    public function __construct(
        OrderContext $context,
        JsonFactory $jsonResultFactory,
    ) {
        $this->context = $context;
        $this->jsonResultFactory = $jsonResultFactory;

        set_error_handler(
            function (int $errno, string $errstr, string $errfile, int $errline): bool {
                $this->context->getLogger()->write('Fiserv API Client threw notice: ' . $errno . ' ' . $errstr);
                return true;
            },
            E_USER_NOTICE
        );
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

        try {
            $event = $this->handleWebhook();
            $this->context->getLogger()->write('Webhook event handling success.');

            return $result->setData($event);
        } catch (\Throwable $th) {
            $this->context->getLogger()->write($th->getMessage());
            $this->context->getResponse()->setContent($th->getMessage());
        }

        return $this->context->getResponse();
    }

    /**
     * Verify message signature on completion request.
     * If message signature is rejected stop handling this order.
     *
     * @param Order $order Order which has to be verified
     */
    private function authenticate(Order $order): void
    {
        $sign = $this->context->getRequest()->getParam('_nonce', false);

        if (!$sign) {
            throw new Exception('Authorization failed. No signature given.');
        }

        $sign = base64_decode($sign);
        $digest = $this->context->createSignature($order);

        if (!hash_equals($digest, $sign)) {
            $this->context->getLogger()->write('DIGEST: ' . $digest);
            $this->context->getLogger()->write('SIGNATURE: ' . $sign);
            throw new Exception('Authorization failed. Signature validation failed.');
        }
    }

    private function handleWebhook(): WebhookEvent
    {
        $this->context->getLogger()->write('### Received webhook event ###');
        $this->context->getLogger()->write($this->context->getRequest()->getContent());

        $content = $this->context->getRequest()->getContent();

        $event = new WebhookEvent($content);
        $orderId = $this->context->getRequest()->getParam('order_id', false);

        if (!$orderId) {
            throw new Exception('Order could not be retrieved');
        }

        $order = $this->context->getOrderRepository()->get(intval($orderId));

        if (!($order instanceof Order)) {
            throw new Exception('Order could not be retrieved');
        }

        $this->authenticate($order);

        $checkoutId = $event->checkoutId;
        $this->context->getLogger()->write('Webhook event for checkout ID ' . $checkoutId);
        $this->updateOrder($order, $event);

        return $event;
    }

    /**
     * Update magento order according to webhook event
     *
     * @param  \Fisrv\Models\WebhookEvent\WebhookEvent $event
     * @return void
     */
    private function updateOrder(Order $order, WebhookEvent $event)
    {
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
                return;
        }

        $this->context->getLogger()->write('Changing order status of order ' . $order->getId() . ' from ' . $order->getStatus() . ' to ' . $status);
        $order->addCommentToStatusHistory('Webhook successfully changed order status to ' . $status);
        // $order->setStatus($status)->setState($status);
        $this->context->getOrderRepository()->save($order);
    }
}
