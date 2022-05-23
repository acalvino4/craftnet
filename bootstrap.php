<?php
/**
 * Craft web bootstrap file
 */

define('CRAFT_BASE_PATH', __DIR__);
define('CRAFT_VENDOR_PATH', CRAFT_BASE_PATH . '/vendor');

// Composer autoloader
require_once CRAFT_VENDOR_PATH . '/autoload.php';

// Load dotenv?
if (class_exists(Dotenv\Dotenv::class)) {
    // By default, this will allow .env file values to override environment variables
    // with matching names. Use `createUnsafeImmutable` to disable this.
    Dotenv\Dotenv::createUnsafeMutable(CRAFT_BASE_PATH)->safeLoad();
}
