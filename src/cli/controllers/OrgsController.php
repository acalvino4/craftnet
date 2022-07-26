<?php

namespace craftnet\cli\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craftnet\behaviors\UserBehavior;
use craftnet\db\Table;
use craftnet\Module;
use craftnet\orgs\Org;
use craftnet\partners\Partner;
use craftnet\plugins\Plugin;
use Throwable;
use yii\console\Controller;
use yii\db\Exception;

class OrgsController extends Controller
{
    /**
     * Converts existing developers and partners to orgs and creates an org owner with matching credentials
     *
     * @return void
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \yii\base\Exception|Throwable
     */
    public function actionConvert(): void
    {
        $partnerOwnerIds = Partner::find()->collect()->pluck('ownerId');
        $developerIds = Plugin::find()->collect()->pluck('developerId');

        $userIds = $developerIds
            ->push(...$partnerOwnerIds)
            ->unique()
            ->all();

        User::find()
            ->status('credentialed')
            ->id($userIds)
            ->collect()
            ->each(function (User $user) {
                $this->stdout("Creating an org for user #$user->id ($user->email) ..." . PHP_EOL);

                if (Org::find()->creatorId($user->id)->exists()) {
                    $this->stdout("Org already converted, skipping." . PHP_EOL);
                    return;
                }

                $partner = Partner::find()->ownerId($user->id)->one();

                $org = new Org();
                $org->title = $partner->title ?? $user->developerName ?? $user->username;
                $org->slug = $partner->websiteSlug ?? $user->username;
                $org->stripeAccessToken = $user->stripeAccessToken;
                $org->stripeAccount = $user->stripeAccount;
                $org->apiToken = $user->apiToken;
                $org->balance = $user->balance;
                $org->creatorId = $user->id;

                $org->setFieldValues([
                    'externalUrl' => $partner->website ?? $user->developerUrl,
                    'location' => $user->location,
                    'payPalEmail' => $user->payPalEmail,
                    'enablePartnerFeatures' => $user->enablePartnerFeatures,
                    'avatar' => $user->photoId ? [$user->photoId] : null,
                ]);

                // TODO: other partner data (partners table)

                $this->stdout("    > Saving org ... ");
                if (!Craft::$app->getElements()->saveElement($org)) {
                    throw new Exception("Couldn't save org: " . implode(', ', $org->getFirstErrors()));
                }
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Removing avatar from user ... ");
                $user->setPhoto(null);
                Craft::$app->getElements()->saveElement($user);
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Adding user as owner of org ... ");
                $org->addOwner($user);
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Relating orders to org ... ");
                $rows = Order::find()->customer($user)->collect()
                    ->map(fn($order) => [
                        $order->id,
                        $org->id,
                    ]);
                Craft::$app->getDb()->createCommand()
                    ->batchInsert(Table::ORGS_ORDERS, ['id', 'orgId'], $rows->all())
                    ->execute();
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Relating plugins to org ... ");
                Craft::$app->getDb()->createCommand()
                    ->update(Table::PLUGINS, ['developerId' => $org->id], ['developerId' => $user->id])
                    ->execute();
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Relating CMS licenses to org ... ");
                Craft::$app->getDb()->createCommand()
                    ->update(Table::CMSLICENSES, ['ownerId' => $org->id], ['ownerId' => $user->id])
                    ->execute();
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Relating plugins licenses to org ... ");
                Craft::$app->getDb()->createCommand()
                    ->update(Table::PLUGINLICENSES, ['ownerId' => $org->id], ['ownerId' => $user->id])
                    ->execute();
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Relating payout items to org ... ");
                Craft::$app->getDb()->createCommand()
                    ->update(Table::PAYOUT_ITEMS, ['developerId' => $org->id], ['developerId' => $user->id])
                    ->execute();
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Relating packages to org ... ");
                Craft::$app->getDb()->createCommand()
                    ->update(Table::PACKAGES, ['developerId' => $org->id], ['developerId' => $user->id])
                    ->execute();
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Relating developer ledger to org ... ");
                Craft::$app->getDb()->createCommand()
                    ->update(Table::DEVELOPERLEDGER, ['developerId' => $org->id], ['developerId' => $user->id])
                    ->execute();
                $this->stdout('done' . PHP_EOL);

                $this->stdout("Done creating org #$org->id" . PHP_EOL . PHP_EOL);
            });
    }

    public function actionCleanup(): void
    {

    }
}
