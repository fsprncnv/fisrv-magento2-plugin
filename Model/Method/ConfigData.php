<?php

namespace Fisrv\Payment\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Helper class to access (and verify) config parameters.
 */
class ConfigData
{
    private const PATH_PROD = 'payment/fisrv_generic/production';

    private const PATH_APIKEY = 'payment/fisrv_generic/apikey';

    private const PATH_APISECRET = 'payment/fisrv_generic/apisecret';

    private const PATH_STOREID = 'payment/fisrv_generic/storeid';

    private const PATH_GENERIC_ENABLED = 'payment/fisrv_generic/active';

    private const PATH_GOOGLEPAY_ENABLED = 'payment/fisrv_googlepay/active';

    private const PATH_APPLEPAY_ENABLED = 'payment/fisrv_applepay/active';

    private const PATH_CARD_ENABLED = 'payment/fisrv_creditcard/active';

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

    public function isProductionMode(?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, self::PATH_PROD) ?? false;
    }

    public function getApiKey(?int $storeId = null): ?string
    {
        return $this->getConfigEntry($storeId, self::PATH_APIKEY);
    }

    public function getApiSecret(?int $storeId = null): ?string
    {
        return $this->getConfigEntry($storeId, self::PATH_APISECRET);
    }

    public function getFisrvStoreId(?int $storeId = null): ?string
    {
        return $this->getConfigEntry($storeId, self::PATH_STOREID);
    }

    public function isGenericEnabled(?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, self::PATH_GENERIC_ENABLED) ?? false;
    }

    public function isGooglepayEnabled(?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, self::PATH_GENERIC_ENABLED) ?? false;
    }

    public function isApplepayEnabled(?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, self::PATH_GENERIC_ENABLED) ?? false;
    }

    public function isCreditcardEnabled(?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, self::PATH_GENERIC_ENABLED) ?? false;
    }

    public function isMethodActive(string $method, ?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, 'payment/' . $method . '/active') ?? false;
    }

    /**
     * Check if config data is complete
     *
     * @return false if any required field is not set, else true
     */
    public function isConfigDataSet(): bool
    {
        foreach ($this->requiredXmlPaths as $path) {
            $entry = $this->getConfigEntry(null, $path);
            if (is_null($entry) ||
                $entry === ''
            ) {
                return false;
            }
        }

        return true;
    }
}
