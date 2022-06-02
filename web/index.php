<?php
/**
 * Craft web bootstrap file
 */

switch ($_SERVER['HTTP_HOST']) {
    case 'api.craftcms.com':
    case 'api.craftcms.test':
    case 'api.craftcms.nitro':
    case 'api.craftcms.next':
    case 'staging.api.craftcms.com':
    case 'craftcmsapi.com':
    case 'api.craftnet.ddev.site':
        define('CRAFT_SITE', 'api');
        break;
    case 'composer.craftcms.com':
    case 'composer.craftcms.test':
    case 'composer.craftcms.nitro':
    case 'composer.craftnet.ddev.site':
        define('CRAFT_SITE', 'composer');
        break;
    case 'console.craftcms.com':
    case 'console.craftcms.test':
    case 'console.craftcms.nitro':
    case 'console.craftcms.next':
    case 'staging.console.craftcms.com':
    case 'console.craftnet.ddev.site':
        define('CRAFT_SITE', 'console');
        break;
    case 'id.craftcms.com':
    case 'id.craftcms.test':
    case 'id.craftcms.nitro':
    case 'id.craftcms.next':
    case 'staging.id.craftcms.com':
    case 'id.craftnet.ddev.site':
        define('CRAFT_SITE', 'craftId');
        break;
    case 'plugins.craftcms.com':
    case 'staging.plugins.craftcms.com':
    case 'plugins.craftcms.test':
    case 'plugins.craftcms.nitro':
    case 'plugins.craftcms.next':
    case 'plugins.craftnet.ddev.site':
        define('CRAFT_SITE', 'plugins');
        break;
}

// Load shared bootstrap
require dirname(__DIR__) . '/bootstrap.php';

// Load and run Craft
/** @var craft\web\Application $app */
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';
$app->run();
