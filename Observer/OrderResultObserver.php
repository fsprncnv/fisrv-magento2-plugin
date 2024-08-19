<?php

namespace Fisrv\Payment\Observer;

use Exception;
use Fisrv\Payment\Logger\DebugLogger;
use Magento\Checkout\Model\Session;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\InvoiceService;

if (file_exists(__DIR__ . '/../vendor/fisrv/php-client/vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/fisrv/php-client/vendor/autoload.php';
}

class OrderResultObserver implements ObserverInterface
{
    private DebugLogger $logger;

    private InvoiceService $invoiceService;

    private TransactionFactory $transactionFactory;

    private OrderRepository $orderRepository;

    private Session $session;

    private $responseFactory;

    private $url;

    public function __construct(
        DebugLogger $logger,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        OrderRepository $orderRepository,
        Session $session,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
    ) {
        $this->logger = $logger;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->orderRepository = $orderRepository;
        $this->session = $session;

        $this->responseFactory = $responseFactory;
        $this->url = $url;
    }

    public function execute(Observer $observer)
    {
        $this->logger->write('--- Run OrderResultObserver ---');
        $order = $observer->getEvent()->getData('order');

        if ($order instanceof Order) {
            throw new Exception('PlaceOrderObserver: Something went wrong when retrieving order object form session.');
        }

        $this->logger->write('--- End OrderResultObserver with order state: ' . $order->getState() . ' ---');
    }
}
