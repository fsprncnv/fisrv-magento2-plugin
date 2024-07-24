<?php

namespace Fisrv\Payment\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Module\ModuleListInterface;

class ConfigData
{
    private const PATH_SANBOX = 'payment/fisrv_generic/sanbox';
    private const PATH_APIKEY = 'payment/fisrv_generic/apikey';
    private const PATH_APISECRET = 'payment/fisrv_generic/apisecret';
    private const PATH_STOREID = 'payment/fisrv_generic/storeid';

    private ScopeConfigInterface $scopeConfig;
    private ModuleListInterface $moduleList;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ModuleListInterface $moduleList
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->moduleList = $moduleList;
    }

    private function getConfigEntry(?int $storeId, string $configXmlPath)
    {
        return $this->scopeConfig->getValue(
            $configXmlPath,
            ScopeInterface::SCOPE_DEFAULT,
            $storeId
        );
    }

    public function getModuleVersion()
    {
        return $this->moduleList->getOne('Fisrv_Payment')['setup_version'];
    }


    public function isSandbox(?int $storeId): bool
    {
        return $this->getConfigEntry($storeId, self::PATH_SANBOX) ?? true;
    }

    public function getApiKey(?int $storeId): string
    {
        return $this->getConfigEntry($storeId, self::PATH_APIKEY);
    }

    public function getApiSecret(?int $storeId): bool
    {
        return $this->getConfigEntry($storeId, self::PATH_APISECRET);
    }

    public function getFisrvStoreId(?int $storeId): bool
    {
        return $this->getConfigEntry($storeId, self::PATH_STOREID);
    }
}