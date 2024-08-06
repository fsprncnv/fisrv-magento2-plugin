<?php

namespace Fisrv\Payment\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Module\ModuleListInterface;

class ConfigData
{
    private const PATH_PROD = 'payment/fisrv_generic/production';
    private const PATH_APIKEY = 'payment/fisrv_generic/apikey';
    private const PATH_APISECRET = 'payment/fisrv_generic/apisecret';
    private const PATH_STOREID = 'payment/fisrv_generic/storeid';

    private array $requiredXmlPaths = [
        self::PATH_APIKEY,
        self::PATH_APISECRET,
        self::PATH_STOREID
    ];

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

    public function isProductionMode(?int $storeId): bool
    {
        return $this->getConfigEntry($storeId, self::PATH_PROD) ?? false;
    }

    public function getApiKey(?int $storeId): ?string
    {
        return $this->getConfigEntry($storeId, self::PATH_APIKEY);
    }

    public function getApiSecret(?int $storeId): ?string
    {
        return $this->getConfigEntry($storeId, self::PATH_APISECRET);
    }

    public function getFisrvStoreId(?int $storeId): ?string
    {
        return $this->getConfigEntry($storeId, self::PATH_STOREID);
    }

    public function isConfigDataSet(): bool
    {
        foreach ($this->requiredXmlPaths as $path) {
            $entry = $this->getConfigEntry(null, $path);
            if (
                is_null($entry) ||
                $entry === ''
            ) {
                return false;
            }
        }

        return true;
    }
}