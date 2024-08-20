<?php

namespace Fisrv\Payment\Model;

use Fisrv\Payment\Model\Method\ConfigData;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    private ConfigData $configData;

    private static array $configStore = [
        'payment' => [
            'fisrv_gateway' => []
        ]
    ];

    public function __construct(
        ConfigData $configData,
    ) {
        $this->configData = $configData;
    }

    /**
     * Provide config data on JS client side.
     * Used on payment selection page to check method availabiltiy.
     *
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $this->addConfig('is_available', $this->configData->isConfigDataSet());
        $this->addConfig('is_admin', false);

        return self::$configStore;
    }

    private function addConfig(string $key, string|bool $value): void
    {
        self::$configStore['payment']['fisrv_gateway'][$key] = $value;
    }
}