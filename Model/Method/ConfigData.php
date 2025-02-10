<?php

namespace Fiserv\Checkout\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\PageCache\Model\Cache\Type;
use Magento\Framework\App\Cache\Type\Config;

/**
 * Helper class to access (and verify) config parameters.
 */
class ConfigData
{
    private array $requiredXmlPaths = [];

    private const PATH_HOST = 'payment/fisrv/host';

    private const FALLBACK_HOST = 'https://checkout-lane.com/';

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
        return $this->moduleList->getOne('Fiserv_Checkout')['setup_version'];
    }

    public function isProductionMode(?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, 'payment/fisrv/sandbox') ?? false;
    }

    public function getApiKey(?int $storeId = null): ?string
    {
        return $this->getConfigEntry($storeId, 'payment/fisrv/apikey');
    }

    public function getApiSecret(?int $storeId = null): ?string
    {
        return $this->getConfigEntry($storeId, 'payment/fisrv/apisecret');
    }

    public function getFisrvStoreId(?int $storeId = null): ?string
    {
        return $this->getConfigEntry($storeId, 'payment/fisrv/storeid');
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

    public function isLoggingEnabled(?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, 'payment/fisrv/debug') ?? false;
    }

    public function isGatewayEnabled(?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, 'payment/fisrv/enabled') ?? true;
    }

    public function isMethodActive(string $method, ?int $storeId = null): bool
    {
        return $this->getConfigEntry($storeId, 'payment/' . $method . '/active') ?? true;
    }

    public function getCustomPaymentMethodName(string $method, ?int $storeId = null): ?string
    {
        return $this->getConfigEntry($storeId, 'payment/' . $method . '/title');
    }


    /**
     * Check if config data is complete
     *
     * @return bool if any required field is not set, else true
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
