<?php

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

if (PHP_VERSION_ID < 80512) {
    throw new \RuntimeException('Fiserv Checkout requires PHP 8.5.12 or later.');
}

if (!class_exists(ComponentRegistrar::class)) {
    return;
}

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Fiserv_Checkout', __DIR__);
