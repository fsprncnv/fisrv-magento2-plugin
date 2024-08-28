<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/magento-stubs-0.php')) {
    include_once __DIR__ . '/magento-stubs-0.php';
}

if (file_exists(__DIR__ . '/magento-stubs-1php')) {
    include_once __DIR__ . '/magento-stubs-1.php';
}

// use Magento\Framework\Component\ComponentRegistrar;

// ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Fisrv_Payment', __DIR__);