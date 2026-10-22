<?php

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

$phpVersionAllowed = (
    PHP_VERSION_ID >= 80509
    || (PHP_VERSION_ID >= 80427 && PHP_VERSION_ID < 80500)
    || (PHP_VERSION_ID >= 80336 && PHP_VERSION_ID < 80400)
    || (PHP_VERSION_ID >= 80235 && PHP_VERSION_ID < 80300)
);

if (!$phpVersionAllowed) {
    throw new \RuntimeException('Fiserv Checkout requires PHP 8.5.12, 8.4.27, 8.3.36, or 8.2.35 or later within the same supported branch.');
}

if (!class_exists(ComponentRegistrar::class)) {
    return;
}

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Fiserv_Checkout', __DIR__);
