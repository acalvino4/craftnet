<?php

namespace craftnet\console\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craftnet\db\Table;
use craftnet\errors\LicenseNotFoundException;
use craftnet\helpers\KeyHelper;
use craftnet\Module;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginEdition;
use craftnet\plugins\PluginLicense;
use yii\base\InvalidArgumentException;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\validators\EmailValidator;

/**
 * Manages plugin licenses.
 *
 * @property Module $module
 */
class PluginLicensesController extends Controller
{
    /**
     * Claims licenses for the user with the given username or email.
     */
    public function actionCreate(): int
    {
        $license = new PluginLicense();

        $plugin = null;
        $edition = null;

        $plugin = $this->_pluginPrompt();
        $edition = $this->_pluginEditionPrompt($plugin);

        $license->pluginHandle = $plugin->handle;
        $license->edition = $edition->handle;

        $cmsLicenseKey = $this->prompt('Craft license key (optional):', [
            'validator' => function(string $input, string &$error = null) {
                try {
                    $this->module->getCmsLicenseManager()->getLicenseByKey($input);
                    return true;
                } catch (LicenseNotFoundException $e) {
                    $error = $e->getMessage();
                    return false;
                }
            },
        ]);

        if ($cmsLicenseKey) {
            $license->cmsLicenseId = $this->module->getCmsLicenseManager()->getLicenseByKey($cmsLicenseKey)->id;
        }

        $license->email = $this->prompt('Owner email:', [
            'required' => true,
            'validator' => function(string $email, string &$error = null) {
                return (new EmailValidator())->validate($email, $error);
            },
        ]);

        if ($license->expirable = $this->confirm('Expirable?')) {
            $license->expiresOn = DateTimeHelper::toDateTime($this->prompt('Expiration date:', [
                'required' => true,
                'validator' => function(string $input) {
                    return DateTimeHelper::toDateTime($input) !== false;
                },
                'default' => (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 year')->format('Y-m-d'),
            ]), false, false);
            $license->autoRenew = $this->confirm('Auto-renew?');
        }

        $license->notes = $this->prompt('Owner-facing notes:') ?: null;
        $license->privateNotes = $this->prompt('Private notes:') ?: null;

        $license->key = $this->prompt('License key (optional):', [
            'validator' => function(string $input, string &$error = null) {
                try {
                    $this->module->getPluginLicenseManager()->normalizeKey($input);
                    return true;
                } catch (InvalidArgumentException $e) {
                    $error = $e->getMessage();
                    return false;
                }
            },
        ]) ?: KeyHelper::generatePluginKey();

        $license->pluginId = $plugin->id;
        $license->editionId = $edition->id;
        $license->ownerId = User::find()->select(['elements.id'])->email($license->email)->scalar() ?: null;
        $license->expired = $license->expiresOn !== null ? $license->expiresOn->getTimestamp() < time() : false;

        if (!$this->module->getPluginLicenseManager()->saveLicense($license)) {
            $this->stderr('Could not save license: ' . implode(', ', $license->getFirstErrors()) . PHP_EOL, Console::FG_RED);
            return 1;
        }

        $this->stdout('License saved: ' . $license->key . PHP_EOL, Console::FG_GREEN);

        if ($this->confirm('Associate the license with an order?')) {
            $orderNumber = $this->prompt('Order number:', [
                'required' => true,
                'validator' => function(string $input) {
                    return Order::find()->number($input)->exists();
                },
            ]);
            $order = Order::find()->number($orderNumber)->one();
            /** @var LineItem[] $lineItems */
            $lineItems = [];
            $lineItemOptions = [];
            foreach ($order->getLineItems() as $i => $lineItem) {
                $key = (string)($i + 1);
                $lineItems[$key] = $lineItem;
                $lineItemOptions[$key] = $lineItem->getDescription();
            }
            $key = $this->select('Which line item?', $lineItemOptions);
            $lineItem = $lineItems[$key];
            Db::insert(Table::PLUGINLICENSES_LINEITEMS, [
                'licenseId' => $license->id,
                'lineItemId' => $lineItem->id,
            ]);
        }

        if ($this->confirm('Create a history record for the license?', true)) {
            $note = $this->prompt('Note: ', [
                'required' => true,
                'default' => "created by {$license->email}" . (isset($order) ? " per order {$order->number}" : ''),
            ]);
            $this->module->getPluginLicenseManager()->addHistory($license->id, $note);
        }

        return 0;
    }

    /**
     * Transfers all licenses from one plugin/edition to another.
     *
     * @return int
     */
    public function actionTransfer(): int
    {
        $oldPlugin = $this->_pluginPrompt('Old plugin:');
        $oldEdition = $this->_pluginEditionPrompt($oldPlugin, 'Old edition:');
        $newPlugin = $this->_pluginPrompt('New plugin:');
        $newEdition = $this->_pluginEditionPrompt($newPlugin, 'New edition:');

        if ($oldEdition->id == $newEdition->id) {
            $this->stdout("That’s the same plugin/edition. Guess we’re done!\n");
            return ExitCode::OK;
        }

        $licenseManager = $this->module->getPluginLicenseManager();
        $allLicenses = $licenseManager->getLicensesByPlugin($oldPlugin->id, $oldEdition->id, true);

        if (empty($allLicenses)) {
            $this->stdout("No licenses found for $oldEdition->description.\n");
            return ExitCode::OK;
        }

        $this->stdout(count($allLicenses), Console::FG_CYAN);
        $this->stdout(" $oldEdition->description licenses found.\n");

        $sendEmail = $this->confirm('Send email to license holders?');

        if (!$this->confirm('Transfer licenses now?')) {
            $this->stdout("Aborted\n");
            return ExitCode::OK;
        }

        $licensesByEmail = ArrayHelper::index($allLicenses, null, function(PluginLicense $license): string {
            return mb_strtolower($license->email);
        });

        $mailer = Craft::$app->getMailer();

        foreach ($licensesByEmail as $email => $licenses) {
            foreach ($licenses as $license) {
                $this->stdout("Transferring $license->shortKey ($license->email) ... ");
                $license->pluginId = $newPlugin->id;
                $license->pluginHandle = $newPlugin->handle;
                $license->editionId = $newEdition->id;
                $license->edition = $newEdition->handle;

                if (!$licenseManager->saveLicense($license)) {
                    $errors = implode('', array_map(function(string $error) {
                        return " - $error\n";
                    }, $license->getFirstErrors()));
                    $this->stdout("validation errors:\n$errors", Console::FG_RED);
                    continue 2;
                }

                $licenseManager->addHistory($license->id, "Transferred from {$oldEdition->getDescription()}");
                $this->stdout("done\n", Console::FG_GREEN);
            }

            if ($sendEmail) {
                $this->stdout('- Sending email ... ');
                $user = User::find()->email($email)->one();
                $message = $mailer
                    ->composeFromKey(Module::MESSAGE_KEY_LICENSE_TRANSFER, compact(
                        'oldPlugin',
                        'oldEdition',
                        'newPlugin',
                        'newEdition',
                        'user',
                        'licenses'
                    ))
                    ->setTo($user ?? $email);

                if ($message->send()) {
                    $this->stdout("done\n", Console::FG_GREEN);
                } else {
                    $this->stderr("error sending email\n", Console::FG_RED);
                }
            }
        }

        return ExitCode::OK;
    }

    /**
     * Prompts for a plugin.
     *
     * @param string $text
     * @return Plugin
     */
    private function _pluginPrompt(string $text = 'Plugin:'): Plugin
    {
        $handle = $this->prompt($text, [
            'required' => true,
            'validator' => function(string $input, string &$error = null) {
                if (!Plugin::find()->handle($input)->exists()) {
                    $error = 'No plugin exists with that handle.';
                    return false;
                }
                return true;
            },
        ]);

        return Plugin::find()->handle($handle)->one();
    }

    /**
     * Prompts for a plugin edition.
     *
     * @param Plugin $plugin
     * @param string $text
     * @return PluginEdition
     */
    private function _pluginEditionPrompt(Plugin $plugin, string $text = 'Edition:'): PluginEdition
    {
        $editions = PluginEdition::find()->pluginId($plugin->id)->indexBy('handle')->all();

        if (empty($editions)) {
            throw new InvalidArgumentException("$plugin->name has no editions");
        }

        if (count($editions) === 1) {
            return reset($editions);
        }

        $options = ArrayHelper::getColumn($editions, 'name');
        $handle = $this->select($text, $options);
        return $editions[$handle];
    }
}
