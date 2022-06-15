<?php

namespace craftnet\logs;

use Bugsnag\Client;
use Bugsnag\Shutdown\ShutdownStrategyInterface;
use Craft;

/**
 * Class PhpShutdownStrategy.
 *
 * Use the built-in PHP shutdown function
 */
class PhpShutdownStrategy implements ShutdownStrategyInterface
{
    /**
     * @param Client $client
     *
     * @return void
     */
    public function registerShutdownStrategy(Client $client): void
    {
        register_shutdown_function(static function() use($client) {
            Craft::$app->getLog()->logger->flush(true);
            $client->flush();
        });
    }
}
