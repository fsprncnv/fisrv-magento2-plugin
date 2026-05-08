<?php

/**
 * PHPStan bootstrap file.
 * Defines global constants and functions provided by the Magento runtime
 * that are not available in this standalone analysis context.
 */

declare(strict_types=1);

if (!defined('BP')) {
    define('BP', __DIR__);
}

if (!function_exists('__')) {
    function __(string $text, mixed ...$args): string
    {
        return $args ? sprintf($text, ...$args) : $text;
    }
}

if (!function_exists('_')) {
    function _(string $text): string
    {
        return $text;
    }
}
