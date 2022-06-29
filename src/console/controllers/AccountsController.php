<?php

namespace craftnet\console\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\Console;
use craft\i18n\Formatter;
use craftnet\behaviors\UserBehavior;
use craftnet\Module;
use craftnet\plugins\Plugin;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Show information about accounts
 *
 * @property Module $module
 */
class AccountsController extends Controller
{
    /**
     * @var int|null The maximum number of orders, Craft licenses, and plugin licenses to show
     */
    public $limit;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        if ($actionID === 'info') {
            $options[] = 'limit';
        }
        return $options;
    }

    /**
     * Shows information about an account
     *
     * @param string $username Username, email, or ID
     * @return int
     */
    public function actionInfo(string $username): int
    {
        if (is_numeric($username)) {
            /** @var User|UserBehavior|null $user */
            $user = User::find()->id($username)->status(null)->one();
        } else {
            /** @var User|UserBehavior|null $user */
            $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($username);
        }

        if (!$user) {
            $this->stderr('Invalid ID, username, or email' . PHP_EOL, Console::FG_RED);
            return 1;
        }

        $this->stdout(PHP_EOL);
        $this->user($user);
        $this->orders($user);
        $this->cmsLicenses($user);
        $this->pluginLicenses($user);
        $this->plugins($user);

        return ExitCode::OK;
    }

    public function actionReconcileOrphanedOrders(): int
    {
        (new Query())
            ->select('*')
            ->from(['{{%craftnet_user_order_email_mismatch}}'])
            ->collect()
            ->each(function(array $row) : void {
                if (!$userByOrderEmail = Craft::$app->getUsers()->getUserByUsernameOrEmail($row['order_email'])) {
                    $this->stderr("User not found: $userByOrderEmail->email. Blame Nate." . PHP_EOL, Console::FG_RED);
                    return;
                }
                if (!$userByUserEmail = Craft::$app->getUsers()->getUserByUsernameOrEmail($row['user_email'])) {
                    $this->stderr("User not found: $userByUserEmail->email. Blame Nate." . PHP_EOL, Console::FG_RED);
                    return;
                }

                if (!$userByOrderEmail->isCredentialed && $userByUserEmail->isCredentialed) {
                    $this->stdout("Transferring Commerce data from #$userByOrderEmail->id to #$userByUserEmail->id ... ", Console::FG_CYAN);
                    if (Commerce::getInstance()->getCustomers()->transferCustomerData($userByOrderEmail, $userByUserEmail)) {
                        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                        return;
                    }
                    $this->stderr('failed' . PHP_EOL, Console::FG_RED);
                }
            });

        return ExitCode::OK;
    }

    protected function user(User $user)
    {
        /** @var User|UserBehavior|null $user */
        $formatter = Craft::$app->getFormatter();

        $this->stdout('ID: ', Console::FG_CYAN);
        $this->stdout($user->id . PHP_EOL);
        $this->stdout('Username: ', Console::FG_CYAN);
        $this->stdout($user->username . PHP_EOL);
        $this->stdout('Email: ', Console::FG_CYAN);
        $this->stdout($user->email . PHP_EOL);
        $this->stdout('Status: ', Console::FG_CYAN);
        $this->stdout(ucfirst($user->getStatus()) . PHP_EOL);
        $this->stdout('Last login: ', Console::FG_CYAN);
        $this->stdout(($user->lastLoginDate ? $formatter->asDate($user->lastLoginDate, Formatter::FORMAT_WIDTH_SHORT) : '') . PHP_EOL);
        $this->stdout('Developer: ', Console::FG_CYAN);
        $this->stdout(($user->isInGroup('developers') ? 'Yes' : 'No') . PHP_EOL);
        $this->stdout('Partner: ', Console::FG_CYAN);
        $this->stdout(($user->enablePartnerFeatures ? 'Yes' : 'No') . PHP_EOL);
        $this->stdout(PHP_EOL);
    }

    protected function orders(User $user)
    {
        $orders = Order::find()
            ->select(['elements.id', 'number', 'dateOrdered', 'totalPrice'])
            ->user($user)
            ->isCompleted()
            ->orderBy(['dateOrdered' => SORT_DESC])
            ->asArray()
            ->all();

        $totalOrders = count($orders);
        if ($this->limit) {
            $orders = array_slice($orders, 0, $this->limit);
        }

        $formatter = Craft::$app->getFormatter();
        foreach ($orders as &$order) {
            $order = [
                $order['id'],
                $order['number'],
                $formatter->asDate($order['dateOrdered'], Formatter::FORMAT_WIDTH_SHORT),
                $formatter->asCurrency($order['totalPrice'], 'USD'),
            ];
        }
        unset($order);

        $this->stdout("Orders ({$totalOrders})" . PHP_EOL . PHP_EOL, Console::FG_CYAN);
        if ($orders) {
            $this->table(['ID', 'Number', 'Date', 'Total'], $orders);
            if ($this->limit && count($orders) !== $totalOrders) {
                $this->stdout('...' . PHP_EOL);
            }
            $this->stdout(PHP_EOL);
        }
    }

    protected function cmsLicenses(User $user)
    {
        $cmsLicenses = $this->module->getCmsLicenseManager()->getLicensesByOwner($user);

        $totalCmsLicenses = count($cmsLicenses);
        if ($this->limit) {
            $cmsLicenses = array_slice($cmsLicenses, 0, $this->limit);
        }

        $formatter = Craft::$app->getFormatter();
        foreach ($cmsLicenses as &$cmsLicense) {
            $cmsLicense = [
                $cmsLicense->id,
                $cmsLicense->getShortKey(),
                ucfirst($cmsLicense->editionHandle),
                $cmsLicense->expiresOn ? $formatter->asDate($cmsLicense->expiresOn, Formatter::FORMAT_WIDTH_SHORT) : '',
            ];
        }
        unset($cmsLicense);

        $this->stdout("Craft Licenses ({$totalCmsLicenses})" . PHP_EOL . PHP_EOL, Console::FG_CYAN);
        if ($cmsLicenses) {
            $this->table(['ID', 'Key', 'Edition', 'Expiry Date'], $cmsLicenses);
            if ($this->limit && count($cmsLicenses) !== $totalCmsLicenses) {
                $this->stdout('...' . PHP_EOL);
            }
            $this->stdout(PHP_EOL);
        }
    }

    protected function pluginLicenses(User $user)
    {
        $pluginLicenses = $this->module->getPluginLicenseManager()->getLicensesByOwner($user);

        $totalPluginLicenses = count($pluginLicenses);
        if ($this->limit) {
            $pluginLicenses = array_slice($pluginLicenses, 0, $this->limit);
        }

        $formatter = Craft::$app->getFormatter();
        foreach ($pluginLicenses as &$pluginLicense) {
            $pluginLicense = [
                $pluginLicense->id,
                $pluginLicense->getShortKey(),
                $pluginLicense->getPlugin()->name,
                $pluginLicense->expiresOn ? $formatter->asDate($pluginLicense->expiresOn, Formatter::FORMAT_WIDTH_SHORT) : '',
            ];
        }
        unset($pluginLicense);

        $this->stdout("Plugin Licenses ({$totalPluginLicenses})" . PHP_EOL . PHP_EOL, Console::FG_CYAN);
        if ($pluginLicenses) {
            $this->table(['ID', 'Key', 'Plugin', 'Expiry Date'], $pluginLicenses);
            if ($this->limit && count($pluginLicenses) !== $totalPluginLicenses) {
                $this->stdout('...' . PHP_EOL);
            }
            $this->stdout(PHP_EOL);
        }
    }

    protected function plugins(User $user)
    {
        /** @var Plugin[] $plugins */
        $plugins = Plugin::find()
            ->developerId($user->id)
            ->status(null)
            ->all();

        $totalPlugins = count($plugins);

        if ($this->limit) {
            $plugins = array_slice($plugins, 0, $this->limit);
        }

        foreach ($plugins as &$plugin) {
            $plugin = [
                $plugin->name,
            ];
        }
        unset($plugin);

        $this->stdout("Plugins ({$totalPlugins})" . PHP_EOL . PHP_EOL, Console::FG_CYAN);
        if ($plugins) {
            $this->table(['Name'], $plugins);
            if ($this->limit && count($plugins) !== $totalPlugins) {
                $this->stdout('...' . PHP_EOL);
            }
            $this->stdout(PHP_EOL);
        }
    }

    protected function table(array $headers = null, array $data)
    {
        // Figure out the max col sizes
        $cellSizes = [];
        foreach (array_merge($data, [$headers ?? []]) as $row) {
            foreach ($row as $i => $cell) {
                if (is_array($cell)) {
                    $cellSizes[$i][] = strlen($cell[0]);
                } else {
                    $cellSizes[$i][] = strlen($cell);
                }
            }
        }

        $maxCellSizes = [];
        foreach ($cellSizes as $i => $sizes) {
            $maxCellSizes[$i] = max($sizes);
        }

        if ($headers !== null) {
            $this->tableRow($headers, $maxCellSizes);
            $this->tableRow([], $maxCellSizes, '-');
        }

        foreach ($data as $row) {
            $this->tableRow($row, $maxCellSizes);
        }
    }

    protected function tableRow(array $row, array $sizes, $pad = ' ')
    {
        foreach ($sizes as $i => $size) {
            if ($i !== 0) {
                $this->stdout('  ');
            }

            $cell = $row[$i] ?? '';
            $value = is_array($cell) ? $cell[0] : $cell;
            $value = str_pad($value, $sizes[$i], $pad, $cell['pad'] ?? STR_PAD_RIGHT);
            if (isset($cell['format']) && $this->isColorEnabled()) {
                $value = Console::ansiFormat($value, $cell['format']);
            }
            $this->stdout($value);
        }

        $this->stdout(PHP_EOL);
    }
}
