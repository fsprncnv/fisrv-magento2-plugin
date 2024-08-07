<?php

namespace Fisrv\Payment\Controller\Checkout;

use Fisrv\Payment\Logger\DebugLogger;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;


class GetActionContext
{
    private DebugLogger $logger;
    private Session $session;
    private RedirectInterface $_redirect;
    private Response $_response;
    private Request $_request;
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
    ) {
        $this->logger = $logger;
        $this->session = $session;
        $this->_redirect = $_redirect;
        $this->_response = $response;
        $this->_request = $request;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
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
}