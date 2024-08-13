<?php

namespace Fisrv\Payment\Model;

use Fisrv\Payment\Model\Method\ConfigData;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Backend\Model\Auth\Session;
use Fisrv\Payment\Logger\DebugLogger;

class ConfigProvider implements ConfigProviderInterface
{
    private ConfigData $configData;
    private Session $session;
    private DebugLogger $logger;

    public function __construct(
        ConfigData $configData,
        Session $session,
        DebugLogger $logger
    ) {
        $this->configData = $configData;
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * Provide config data on JS client side.
     * Used on payment selection page to check method availabiltiy.
     *
     * {@inheritdoc}
     */
    public function getConfig()
    {
        // @todo Check if current user is admin
        $this->logger->write($this->session);

        return [
            'payment' => [
                'fisrv_gateway' => [
                    'is_available' => $this->configData->isConfigDataSet(),
                    'is_admin' => false,
                ]
            ]
        ];
    }
}