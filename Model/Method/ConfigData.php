<?php

namespace Fiserv\Checkout\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\PageCache\Model\Cache\Type;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Store\Model\StoreManager;

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
    private StoreManager $storeManager;

    public const METHOD_CARD = 'fisrv_creditcard';
    public const METHOD_GOOGLE_PAY = 'fisrv_creditcard';
    public const PLUGIN_VERSION = '1.0.6';

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ModuleListInterface $moduleList,
        WriterInterface $writer,
        TypeListInterface $typeList,
        StoreManager $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->moduleList = $moduleList;
        $this->writer = $writer;
        $this->typeList = $typeList;
        $this->storeManager = $storeManager;
    }

    private function getConfigEntry(string $configXmlPath, ?string $storeId = null): mixed
    {
        return $this->scopeConfig->getValue(
            $configXmlPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId ?? $this->storeManager->getStore()->getId()
        );
    }

    public function getModuleVersion(): string
    {
        return self::PLUGIN_VERSION;
    }

    public function isProductionMode(): bool
    {
        return $this->getConfigEntry('payment/fisrv/sandbox') ?? false;
    }

    public function getApiKey(): ?string
    {
        return $this->getConfigEntry('payment/fisrv/apikey');
    }

    public function getApiSecret(): ?string
    {
        return $this->getConfigEntry('payment/fisrv/apisecret');
    }

    public function getFisrvStoreId(): ?string
    {
        return $this->getConfigEntry('payment/fisrv/storeid');
    }

    public function getCheckoutHost(): string
    {
        $this->typeList->cleanType(Config::TYPE_IDENTIFIER);
        $this->typeList->cleanType(Type::TYPE_IDENTIFIER);
        return $this->getConfigEntry(self::PATH_HOST) ?? self::FALLBACK_HOST;
    }

    public function setCheckoutHost(string $value): void
    {
        $parsed = parse_url($value);
        $this->writer->save(self::PATH_HOST, $parsed['scheme'] . '://' . $parsed['host'] . '/');
    }

    public function isLoggingEnabled(): bool
    {
        return $this->getConfigEntry('payment/fisrv/debug') ?? true;
    }

    public function isModuleEnabled($storeId): bool
    {
        return $this->getConfigEntry('payment/fisrv/enabled', $storeId) ?? false;
    }

    public function isMethodActive(string $method): bool
    {
        return $this->getConfigEntry('payment/' . $method . '/active') ?? true;
    }

    public function getCustomPaymentMethodName(string $method): ?string
    {
        return $this->getConfigEntry('payment/' . $method . '/title');
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
