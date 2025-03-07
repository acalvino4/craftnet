<?php

use craft\helpers\App;
use craft\log\Dispatcher;
use craft\log\MonologTarget;
use craftnet\logs\PhpShutdownStrategy;
use craftnet\services\Oauth;
use Illuminate\Support\Collection;
use Logtail\Monolog\LogtailHandler;
use MeadSteve\MonoSnag\BugsnagHandler;
use yii\web\Application as WebApplication;

return [
    '*' => [
        'id' => 'craftnet',
        'bootstrap' => [
            'craftnet',
            'oauth-server',
            'queue',
        ],
        'modules' => [
            'craftnet' => [
                'class' => \craftnet\Module::class,
                'components' => [
                    'cmsLicenseManager' => [
                        'class' => craftnet\cms\CmsLicenseManager::class,
                        'devDomains' => require __DIR__ . '/dev-domains.php',
                        'publicDomainSuffixes' => require __DIR__ . '/public-domain-suffixes.php',
                        'devSubdomainWords' => require __DIR__ . '/dev-subdomain-keywords.php'
                    ],
                    'invoiceManager' => [
                        'class' => craftnet\invoices\InvoiceManager::class,
                    ],
                    'pluginLicenseManager' => [
                        'class' => craftnet\plugins\PluginLicenseManager::class,
                    ],
                    'packageManager' => [
                        'class' => craftnet\composer\PackageManager::class,
                        'githubFallbackTokens' => App::env('GITHUB_FALLBACK_TOKENS'),
                        'requirePluginVcsTokens' => false,
                    ],
                    'payoutManager' => [
                        'class' => \craftnet\payouts\PayoutManager::class,
                    ],
                    'jsonDumper' => [
                        'class' => craftnet\composer\JsonDumper::class,
                        'composerWebroot' => App::env('COMPOSER_WEBROOT'),
                    ],
                    'oauth' => [
                        'class' => Oauth::class,
                        'appTypes' => [
                            Oauth::PROVIDER_GITHUB => [
                                'class' => 'Github',
                                'oauthClass' => League\OAuth2\Client\Provider\Github::class,
                                'clientIdKey' => App::env('GITHUB_APP_CLIENT_ID'),
                                'clientSecretKey' => App::env('GITHUB_APP_CLIENT_SECRET'),
                                'scope' => ['user:email', 'write:repo_hook', 'public_repo'],
                            ],
                            Oauth::PROVIDER_BITBUCKET => [
                                'class' => 'Bitbucket',
                                'oauthClass' => Stevenmaguire\OAuth2\Client\Provider\Bitbucket::class,
                                'clientIdKey' => App::env('BITBUCKET_APP_CLIENT_ID'),
                                'clientSecretKey' => App::env('BITBUCKET_APP_CLIENT_SECRET'),
                                'scope' => 'account',
                            ],
                        ]
                    ],
                    'saleManager' => [
                        'class' => craftnet\sales\SaleManager::class,
                    ]
                ]
            ],
            'oauth-server' => [
                'class' => craftnet\oauthserver\Module::class,
            ],
        ],
        'components' => [
            'errorHandler' => [
                'memoryReserveSize' => 1024000
            ],
            'schedule' => [
                'class' => omnilight\scheduling\Schedule::class,
                'cliScriptName' => 'craft',
            ],
            'log' => static function() {
                $dispatcher = new Dispatcher();

                Collection::make($dispatcher->targets)
                    ->each(function(MonologTarget $target) {
                        $logtailToken = App::env('LOGTAIL_TOKEN');
                        $bugsnagApiKey = App::env('BUGSNAG_API_KEY');

                        if ($logtailToken) {
                            $target->getLogger()->pushHandler((new LogtailHandler($logtailToken)));
                        }

                        if ($bugsnagApiKey) {
                            $bugsnagClient = Bugsnag\Client::make($bugsnagApiKey);
                            $bugsnagClient->setReleaseStage(App::env('CRAFT_ENVIRONMENT'));
                            $shutdownStrategy = new PhpShutdownStrategy();
                            $shutdownStrategy->registerShutdownStrategy($bugsnagClient);
                            $target->getLogger()->pushHandler((new BugsnagHandler($bugsnagClient)));
                        }
                    });

                if (YII_DEBUG) {
                    $levels = yii\log\Logger::LEVEL_ERROR | yii\log\Logger::LEVEL_WARNING | yii\log\Logger::LEVEL_INFO | yii\log\Logger::LEVEL_TRACE | yii\log\Logger::LEVEL_PROFILE;
                } else {
                    $levels = yii\log\Logger::LEVEL_ERROR | yii\log\Logger::LEVEL_WARNING;
                }

                $dispatcher->targets['db'] = Craft::createObject([
                    'class' => craftnet\logs\DbTarget::class,
                    'logTable' => 'apilog.logs',
                    'levels' => $levels,
                    'enabled' => App::env('CRAFT_ENVIRONMENT') === 'prod',
                ]);

                return $dispatcher;
            },
        ],
    ],
    'prod' => [
        'bootstrap' => [
            'dlq',
        ],
        'components' => [
            'redis' => [
                'class' => yii\redis\Connection::class,
                'hostname' => App::env('ELASTICACHE_HOSTNAME'),
                'port' => App::env('ELASTICACHE_PORT'),
                'database' => 0,
            ],
            'cache' => [
                'class' => yii\redis\Cache::class,
            ],
            'mutex' => [
                'class' => \yii\redis\Mutex::class,
            ],
            'queue' => [
                'class' => \yii\queue\sqs\Queue::class,
                'url' => App::env('SQS_URL'),
                'key' => App::env('AWS_ACCESS_KEY_ID'),
                'secret' => App::env('AWS_SECRET_ACCESS_KEY'),
                'region' => App::env('REGION'),
            ],
            'dlq' => [
                'class' => \yii\queue\sqs\Queue::class,
                'url' => App::env('SQS_DEAD_LETTER_URL'),
                'key' => App::env('AWS_ACCESS_KEY_ID'),
                'secret' => App::env('AWS_SECRET_ACCESS_KEY'),
                'region' => App::env('REGION'),
            ],
            'session' => function() {
                $config = craft\helpers\App::sessionConfig();
                $config['class'] = yii\redis\Session::class;
                $stateKeyPrefix = md5('Craft.' . craft\web\Session::class . '.' . Craft::$app->id);
                $config['flashParam'] = $stateKeyPrefix . '__flash';
                $config['authAccessParam'] = $stateKeyPrefix . '__auth_access';
                return Craft::createObject($config);
            },

            'db' => function() {
                // Get the default component config
                $config = craft\helpers\App::dbConfig();

                if (Craft::$app instanceof WebApplication) {
                    // Use read/write query splitting
                    // (https://www.yiiframework.com/doc/guide/2.0/en/db-dao#read-write-splitting)

                    // Define the default config for replica DB connections
                    $config['replicaConfig'] = [
                        'username' => App::env('CRAFT_DB_USER'),
                        'password' => App::env('CRAFT_DB_PASSWORD'),
                        'tablePrefix' => App::env('CRAFT_DB_TABLE_PREFIX'),
                        'attributes' => [
                            // Use a smaller connection timeout
                            PDO::ATTR_TIMEOUT => 10,
                        ],
                        'charset' => 'utf8',
                    ];

                    // Define the replica DB connections
                    $config['replicas'] = [
                        [
                            'dsn' => App::env('DB_READ_DSN_1')
                        ],
                    ];
                }

                // Instantiate and return it
                return Craft::createObject($config);
            },
        ],
    ],
    'dev' => [
        'components' => [
            'api' => function() {
                $client = Craft::createGuzzleClient([
                    'base_uri' => App::env('URL_API') . 'v1/',
                    'verify' => false,
                    'query' => ['XDEBUG_SESSION_START' => 14076],
                ]);
                return new \craft\services\Api([
                    'client' => $client,
                ]);
            },
        ]
    ],
];