<?php

namespace Fiserv\Checkout\Model;

use Fiserv\Checkout\Model\Method\ConfigData;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;

class ConfigProvider implements ConfigProviderInterface
{
    private ConfigData $configData;
    private Repository $repository;

    public function __construct(
        ConfigData $configData,
        Repository $repository,
    ) {
        $this->configData = $configData;
        $this->repository = $repository;
    }

    public function getLogo(string $code): string
    {
        return $this->repository->getUrl(sprintf('%s::images/%s.svg', 'Fiserv_Checkout', str_replace('_', '-', $code)));
    }

    /**
     * Provide config data on JS client side.
     * Used on payment selection page to check method availabiltiy.
     *
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'fisrv_gateway' => [
                    'is_available' => true,
                    'fisrv_generic' => [
                        'logo' => $this->getLogo('fisrv_generic')
                    ],
                    'fisrv_creditcard' => [
                        'logo' => $this->getLogo('fisrv_creditcard')
                    ],
                    'fisrv_applepay' => [
                        'logo' => $this->getLogo('fisrv_applepay')
                    ],
                    'fisrv_googlepay' => [
                        'logo' => $this->getLogo('fisrv_googlepay')
                    ],
                ]
            ]
        ];
    }
}