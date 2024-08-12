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


class GetActionContext
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
     * Url builder for actions
     * 
     * @param 
     */
    public function getUrl(string $action, bool $internal = false, array $query = [])
    {
        return $this->url->getUrl(($internal ? 'fisrv/checkout/' : '') . $action, [
            '_query' => $query
        ]);
    }

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