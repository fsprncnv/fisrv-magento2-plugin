<?php

namespace Fisrv\Payment\Controller\Checkout;

use Fisrv\Payment\Logger\DebugLogger;
use Fisrv\Payment\Model\Method\ConfigData;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

/**
 * Helper class serving as context containing commonly
 * used dependency injections on GET actions and other helper methods.
 */
class OrderContext
{
    private const SIGNATURE_LIFETIME = 86400;

    private DebugLogger $logger;
    private Session $session;
    private RedirectInterface $_redirect;
    private Response $_response;
    private Request $_request;
    private UrlInterface $url;
    private ConfigData $_config;
    private OrderRepository $_orderRepository;

    public MessageManagerInterface $messageManager;
    public RedirectFactory $resultRedirectFactory;

    public function __construct(
        DebugLogger $logger,
        Session $session,
        RedirectInterface $_redirect,
        Response $response,
        Request $request,
        MessageManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory,
        UrlInterface $url,
        ConfigData $_config,
        OrderRepository $_orderRepository
    ) {
        $this->logger = $logger;
        $this->session = $session;
        $this->_redirect = $_redirect;
        $this->_response = $response;
        $this->_request = $request;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->url = $url;
        $this->_config = $_config;
        $this->_orderRepository = $_orderRepository;
    }

    public function getRedirect(
        string $path,
        array $arguments = array())
    {
        return $this->_redirect($path, $arguments);
    }

    public function getRequest(): Request
    {
        return $this->_request;
    }

    public function getResponse(): Response
    {
        return $this->_response;
    }

    public function getLogger(): DebugLogger
    {
        return $this->logger;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function getConfigData(): ConfigData
    {
        return $this->_config;
    }

    public function getOrderRepository(): OrderRepository
    {
        return $this->_orderRepository;
    }

    /**
     * Set redirect into response
     *
     * @param string $path
     * @param array $arguments
     */
    public function _redirect($path, $arguments = [])
    {
        $this->_redirect->redirect($this->_response, $path, $arguments);
        return $this->_response;
    }

    /**
     * Url builder for magento routes and action endpoints
     * This method is a shorthand for internal action routes e.g.:
     * getUrl('statusaction', true, [id => 3]) -> {magento site url}/fisrv/checkout/statusaction?id=3
     * 
     * Otherwise a standard URL builder for magento routes and pages e.g.:
     * getUrl('checkout/cart) -> {magento site url}/checkout/cart 
     * 
     * @param string $path URL path
     * @param bool $internal If true, appends default plugin action route before path parameter
     * @param array $query URL query parameters as list
     * @return string Full URL path with root and queries
     */
    public function getUrl(string $path, bool $internal = false, array $query = []): string
    {
        return $this->url->getUrl(($internal ? 'fisrv/checkout/' : '') . $path, [
            '_query' => $query
        ]);
    }

    /**
     * Creates a message signature relating to an order.
     * This signature is used for basic authentication e.g. on Fiserv checkout redirection.
     * The lifetime of a signature is one day.
     * 
     * @param Order $order Order to be created a signature for
     * @return string SHA256 hash from session identifiers
     */
    public function createSignature(Order $order)
    {
        return hash_hmac(
            'sha256',
            ceil(time() / (self::SIGNATURE_LIFETIME / 2))
            . '|' .
            $this->getSession()->getSessionId() . '|' .
            $this->getConfigData()->getApiKey() . '|' .
            $order->getId(),
            $order->getProtectCode(),
            false
        );
    }
}