<?php

namespace craftnet\cli\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craftnet\behaviors\UserBehavior;
use craftnet\db\Table;
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

        // All plugin developers and partner owners
        $existingUserIds = $developerIds
            ->push(...$partnerOwnerIds)
            ->unique()
            ->all();

        User::find()
            ->status('credentialed')
            ->id($existingUserIds)
            ->collect()
            ->each(function(User $user) {
                $this->stdout("Creating an org for user #$user->id ($user->email) ..." . PHP_EOL);

                $partner = Partner::find()->ownerId($user->id)->one();

                $org = new Org();
                $org->title = $partner->title ?? $user->developerName ?? $user->username;
                $org->slug = $partner->websiteSlug ?? $user->username;
                $org->stripeAccessToken = $user->stripeAccessToken;
                $org->stripeAccount = $user->stripeAccount;
                $org->apiToken = $user->apiToken;
                $org->creatorId = $user->id;

                $org->setFieldValues([
                    'externalUrl' => $partner->website ?? $user->developerUrl,
                    'location' => $user->location,
                    'payPalEmail' => $user->payPalEmail,
                    'enablePartnerFeatures' => $user->enablePartnerFeatures,
                ]);
                // TODO: other partner data (partners table)
                // TODO: other developer data (balance)

                $this->stdout("    > Saving org ... ");
                if (!Craft::$app->getElements()->saveElement($org, false)) {
                    throw new Exception("Couldn't save org: " . implode(', ', $org->getFirstErrors()));
                }
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

                $this->stdout("Done creating org #$org->id" . PHP_EOL . PHP_EOL);
            });
    }
}
