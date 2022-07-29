<?php

namespace craftnet\cli\controllers;

use Craft;
use craft\commerce\behaviors\CustomerBehavior;
use craft\commerce\elements\Order;
use craft\commerce\models\PaymentSource;
use craft\commerce\Plugin as Commerce;
use craft\commerce\stripe\gateways\PaymentIntents as StripeGateway;
use craft\commerce\stripe\models\forms\payment\PaymentIntent as PaymentForm;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craftnet\base\LicenseInterface;
use craftnet\Module;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Handles tasks that apply to both Craft and plugin licenses.
 *
 * @property Module $module
 */
class LicensesController extends Controller
{
    /**
     * Sends reminders to people whose Craft/plugin license(s) will be expiring in the next 14-30 days.
     *
     * @return int
     */
    public function actionSendReminders(): int
    {
        $cmsLicenseManager = $this->module->getCmsLicenseManager();
        $pluginLicenseManager = $this->module->getPluginLicenseManager();

        // Find licenses that need reminders
        $this->stdout('Finding licenses that are due for reminders ... ', Console::FG_YELLOW);
        $licenses = array_merge(
            $cmsLicenseManager->getRemindableLicenses(),
            $pluginLicenseManager->getRemindableLicenses()
        );
        $this->stdout('done (' . count($licenses) . ' licenses found)' . PHP_EOL, Console::FG_YELLOW);

        if (empty($licenses)) {
            $this->stdout('Nothing to send.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout('Sending reminders ...' . PHP_EOL, Console::FG_YELLOW);

        // Group by owner (or email if unclaimed)
        $licenses = ArrayHelper::index($licenses, null, function(LicenseInterface $license) {
            $ownerId = $license->getOwnerId();
            return $ownerId ? "owner-{$ownerId}" : mb_strtolower($license->getEmail());
        });

        $mailer = Craft::$app->getMailer();

        foreach ($licenses as $ownerKey => $ownerLicenses) {
            try {
                /** @var string $email */
                /** @var User|null $user */
                [$email, $user] = $this->_resolveOwnerKey($ownerKey);

                // Lock in the renewal prices
                /** @var LicenseInterface[] $ownerLicenses */
                foreach ($ownerLicenses as $license) {
                    if ($license->getOwnerId() && $license->getWillAutoRenew()) {
                        $newRenewalPrice = $license->getEdition()->getRenewal()->getPrice();
                        if ($license->getRenewalPrice() !== $newRenewalPrice) {
                            $license->setRenewalPrice($newRenewalPrice);
                        }
                    }
                }

                $ownerLicensesByType = ArrayHelper::index($ownerLicenses, null, function(LicenseInterface $license) {
                    return $license->getOwnerId() && $license->getWillAutoRenew() ? 'auto' : 'manual';
                });

                $this->stdout("    - Emailing {$email} about " . count($ownerLicenses) . ' licenses ... ', Console::FG_YELLOW);

                $message = $mailer
                    ->composeFromKey(Module::MESSAGE_KEY_LICENSE_REMINDER, ['licenses' => $ownerLicensesByType])
                    ->setTo($user ?? $email);

                if (!$message->send()) {
                    $this->stderr('error sending email' . PHP_EOL, Console::FG_RED);
                    continue;
                }

                $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

                // Mark the licenses as reminded so we don't send this again for them until the next cycle
                foreach ($ownerLicenses as $license) {
                    $license->markAsReminded();
                }
            } catch (\Throwable $e) {
                // Don't let this stop us from sending other reminders
                $this->stdout('An error occurred: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                Craft::$app->getErrorHandler()->logException($e);
            }
        }

        $this->stdout('Done sending reminders.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Auto-renews or expires licenses that are due for it.
     *
     * @return int
     */
    public function actionProcessExpiredLicenses(): int
    {
        if ($this->interactive && $this->confirm('Add a note?')) {
            $note = $this->prompt('Note:');
        }

        $cmsLicenseManager = $this->module->getCmsLicenseManager();
        $pluginLicenseManager = $this->module->getPluginLicenseManager();

        // Find freshly-expired licenses
        $this->stdout('Finding freshly-expired licenses ... ', Console::FG_YELLOW);
        $licenses = array_merge(
            $cmsLicenseManager->getFreshlyExpiredLicenses(),
            $pluginLicenseManager->getFreshlyExpiredLicenses()
        );
        $this->stdout('done (' . count($licenses) . ' licenses found)' . PHP_EOL, Console::FG_YELLOW);

        if (empty($licenses)) {
            $this->stdout('No licenses have expired.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout('Processing licenses ...' . PHP_EOL, Console::FG_YELLOW);

        // Group by owner (or email if unclaimed)
        $licenses = ArrayHelper::index($licenses, null, function(LicenseInterface $license) {
            $ownerId = $license->getOwnerId();
            return $ownerId ? "owner-{$ownerId}" : mb_strtolower($license->getEmail());
        });

        $mailer = Craft::$app->getMailer();

        foreach ($licenses as $ownerKey => $ownerLicenses) {
            try {
                /** @var string $email */
                /** @var User|null $user */
                [$email, $user, $usingOwnerId] = $this->_resolveOwnerKey($ownerKey);

                /** @var LicenseInterface[] $renewLicenses */
                /** @var LicenseInterface[] $expireLicenses */
                if ($user === null || !$usingOwnerId) {
                    $renewLicenses = [];
                    $expireLicenses = $ownerLicenses;
                } else {
                    [$renewLicenses, $expireLicenses] = $this->_findRenewableLicenses($ownerLicenses);
                }

                $autoRenewFailed = false;
                $redirect = null;
                $licensesToExpire = array_merge($expireLicenses);

                // If there are any licenses that should be auto-renewed, give that a shot
                if (!empty($renewLicenses)) {
                    if (!$this->_autoRenewLicenses($renewLicenses, $user, $redirect)) {
                        $licensesToExpire = array_merge($licensesToExpire, $renewLicenses);
                        if (!$redirect) {
                            $autoRenewFailed = true;
                        }
                    }
                }

                // Expire the licenses, including any that couldn't auto-renew successfully.
                $this->stdout('    - Expiring ' . count($licensesToExpire) . " licenses for {$email} ... ", Console::FG_YELLOW);
                foreach ($licensesToExpire as $license) {
                    $license->markAsExpired();
                }
                $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

                // Send a notification email
                $this->stdout("    - Emailing {$email} about " . count($ownerLicenses) . ' licenses ... ', Console::FG_YELLOW);

                $message = $mailer
                    ->composeFromKey(Module::MESSAGE_KEY_LICENSE_NOTIFICATION, [
                        'renewLicenses' => $renewLicenses,
                        'expireLicenses' => $expireLicenses,
                        'autoRenewFailed' => $autoRenewFailed,
                        'redirect' => $redirect,
                        'note' => $note ?? null,
                    ])
                    ->setTo($user ?? $email);

                if ($message->send()) {
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                } else {
                    $this->stderr('error sending email' . PHP_EOL, Console::FG_RED);
                }
            } catch (\Throwable $e) {
                // Don't let this stop us from processing other licenses
                $this->stdout('An error occurred: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                Craft::$app->getErrorHandler()->logException($e);
            }
        }

        $this->stdout('Done processing licenses.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Returns the email and user account (if one exists) for the given license owner key.
     *
     * @param string $ownerKey
     * @return array
     */
    private function _resolveOwnerKey(string $ownerKey): array
    {
        if ($usingOwnerId = (bool)preg_match('/^owner-(\d+)$/', $ownerKey, $matches)) {
            /** @var User $user */
            $user = User::find()->id((int)$matches[1])->status(null)->one();
            $email = $user->email;
        } else {
            $email = $ownerKey;
            $user = User::find()->email($email)->status(null)->one();
        }

        return [$email, $user, $usingOwnerId];
    }

    /**
     * Splits a list of licenses into an array of licenses that should be auto-renewed and an array of licenses
     * that should expire.
     *
     * @param LicenseInterface[] $licenses
     * @return array
     */
    private function _findRenewableLicenses(array $licenses): array
    {
        $utc = new \DateTimeZone('UTC');
        $minRenewalDate = (new \DateTime('midnight', $utc))->modify('-14 days');

        $licensesByType = ArrayHelper::index($licenses, null, function(LicenseInterface $license) use ($utc, $minRenewalDate) {
            if ($license->getWillAutoRenew() && $license->getWasReminded() && $license->getRenewalPrice()) {
                // Only auto-renew if it just expired yesterday
                $expiryDate = $license->getExpiryDate();
                $expiryDate->setTimezone($utc);
                if ($expiryDate >= $minRenewalDate) {
                    return 'renew';
                }
            }
            return 'expire';
        });

        return [
            $licensesByType['renew'] ?? [],
            $licensesByType['expire'] ?? [],
        ];
    }

    /**
     * Attempts to auto-renew some licenses.
     *
     * @param LicenseInterface[] $licenses
     * @param User $user
     * @param string|null $redirect The redirect URL that can be used to complete the renewal
     * @return bool Whether it was successful
     */
    private function _autoRenewLicenses(array $licenses, User $user, ?string &$redirect): bool
    {
        try {
            // Make sure they have a Commerce customer record
            $commerce = Commerce::getInstance();

            /** @var User|CustomerBehavior $user */
            if (!$user->primaryBillingAddressId) {
                return false;
            }

            // Make sure they have a payment source
            /** @var PaymentSource|null $paymentSource */
            $paymentSource = ArrayHelper::firstValue($commerce->getPaymentSources()->getAllPaymentSourcesByCustomerId($user->id));
            if ($paymentSource === null) {
                return false;
            }

            $this->stdout('    - Creating order for ' . count($licenses) . " licenses for {$user->email} ... ", Console::FG_YELLOW);

            $order = new Order([
                'number' => $commerce->getCarts()->generateCartNumber(),
                'currency' => 'USD',
                'paymentCurrency' => 'USD',
                'gatewayId' => App::env('STRIPE_GATEWAY_ID'),
                'orderLanguage' => Craft::$app->language,
                'customerId' => $user->id,
                'email' => $user->email,
            ]);

            $order->cancelUrl = App::parseEnv('$URL_ID') . 'payment';
            $order->returnUrl = App::parseEnv('$URL_ID') . 'thank-you';

            // Save the cart so it gets an ID
            $elementsService = Craft::$app->getElements();
            if (!$elementsService->saveElement($order)) {
                throw new \Exception('Could not save the cart: ' . implode(', ', $order->getErrorSummary(true)));
            }

            // Add the line items to the cart
            $lineItemsService = $commerce->getLineItems();
            foreach ($licenses as $license) {
                $renewalId = $license->getEdition()->getRenewal()->getId();
                $lineItem = $lineItemsService->resolveLineItem($order, $renewalId, [
                    'licenseKey' => $license->getKey(),
                    'lockedPrice' => $license->getRenewalPrice(),
                    'expiryDate' => '1y', // set to expire a year later, regardless of the original expiry date
                ]);
                $lineItem->qty = 1;
                $order->addLineItem($lineItem);
            }

            // Recalculate the order
            $order->recalculate();

            // Resave the order
            if (!$elementsService->saveElement($order)) {
                throw new \Exception('Could not save the cart: ' . implode(', ', $order->getErrorSummary(true)));
            }

            // Pay for it
            /** @var StripeGateway $gateway */
            $gateway = $commerce->getGateways()->getGatewayById(App::env('STRIPE_GATEWAY_ID'));
            /** @var PaymentForm $paymentForm */
            $paymentForm = $gateway->getPaymentFormModel();
            $paymentForm->populateFromPaymentSource($paymentSource);
            $commerce->getPayments()->processPayment($order, $paymentForm, $redirect, $transaction);
        } catch (\Throwable $e) {
            $this->stderr(PHP_EOL . 'error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            Craft::$app->getErrorHandler()->logException($e);
            return false;
        }

        if (!$order->isCompleted) {
            $this->stdout('incomplete' . PHP_EOL, Console::FG_GREY);
            return false;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return true;
    }
}
