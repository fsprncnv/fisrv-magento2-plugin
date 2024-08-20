<?php

namespace Fisrv\Payment\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\PageCache\Model\Cache\Type;
use Magento\Framework\App\Cache\Type\Config;

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

    private const PATH_HOST = 'payment/fisrv_generic/host';

    private const FALLBACK_HOST = 'https://checkout-lane.com/';

    private array $requiredXmlPaths = [
        self::PATH_APIKEY,
        self::PATH_APISECRET,
        self::PATH_STOREID
    ];

    private ScopeConfigInterface $scopeConfig;

    private ModuleListInterface $moduleList;

    private WriterInterface $writer;

    private TypeListInterface $typeList;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ModuleListInterface $moduleList,
        WriterInterface $writer,
        TypeListInterface $typeList
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->moduleList = $moduleList;
        $this->writer = $writer;
        $this->typeList = $typeList;
    }

    private function getConfigEntry(?int $storeId, string $configXmlPath)
    {
        return $this->scopeConfig->getValue(
            $configXmlPath,
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

    public function getCheckoutHost(): string
    {
        $this->typeList->cleanType(Config::TYPE_IDENTIFIER);
        $this->typeList->cleanType(Type::TYPE_IDENTIFIER);
        return $this->getConfigEntry(0, self::PATH_HOST) ?? self::FALLBACK_HOST;
    }

    public function setCheckoutHost(string $value): void
    {
        $parsed = parse_url($value);
        $this->writer->save(self::PATH_HOST, $parsed['scheme'] . '://' . $parsed['host'] . '/');
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
            if (!$this->scopeConfig->isSetFlag($path)) {
                return false;
            }
        }

        return true;
    }
}