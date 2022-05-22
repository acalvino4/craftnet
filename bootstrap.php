<?php
/**
 * Craft web bootstrap file
 */

switch ($_SERVER['HTTP_HOST'] ?? null) {
    case 'api.craftcms.com':
    case 'api.craftcms.test':
    case 'api.craftcms.nitro':
    case 'api.craftcms.next':
    case 'staging.api.craftcms.com':
    case 'craftcmsapi.com':
        define('CRAFT_SITE', 'api');
        break;
    case 'composer.craftcms.com':
    case 'composer.craftcms.test':
    case 'composer.craftcms.nitro':
        define('CRAFT_SITE', 'composer');
        break;
    case 'id.craftcms.com':
    case 'id.craftcms.test':
    case 'id.craftcms.nitro':
    case 'id.craftcms.next':
    case 'staging.id.craftcms.com':
        define('CRAFT_SITE', 'craftId');
        break;
    case 'plugins.craftcms.com':
    case 'staging.plugins.craftcms.com':
    case 'plugins.craftcms.test':
    case 'plugins.craftcms.nitro':
    case 'plugins.craftcms.next':
        define('CRAFT_SITE', 'plugins');
        break;
}

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
