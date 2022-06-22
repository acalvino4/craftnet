<?php

namespace craftnet\cli\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craftnet\behaviors\UserBehavior;
use craftnet\db\Table;
use craftnet\partners\Partner;
use craftnet\plugins\Plugin;
use Throwable;
use yii\console\Controller;
use yii\db\Exception;

class OrgsController extends Controller
{
    /**
     * Converts existing developers and partners to orgs and creates an org admin with matching credentials
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
            ->each(function(User $existingUser) use($developerIds) {
                /** @var User|UserBehavior $existingUser */
                $email = $existingUser->email;
                $username = $existingUser->username;
                $active = $existingUser->active;
                $pending = $existingUser->pending;

                $this->stdout("Converting user #$existingUser->id ($existingUser->email) to org ..." . PHP_EOL);

                if (!Craft::$app->getUsers()->removeCredentials($existingUser)) {
                    throw new Exception("Couldn't remove credentials: " . implode(', ', $existingUser->getFirstErrors()));
                }

                // Save w/o user/email so new admin user can validate.
                $partner = Partner::find()->ownerId($existingUser->id)->one();

                $existingUser->email = null;
                $existingUser->username = null;
                $existingUser->websiteSlug = $partner->websiteSlug ?? $username;
                $existingUser->displayName = $partner->title ?? $existingUser->developerName ?? $existingUser->getName();
                $existingUser->websiteUrl = $partner->website ?? $existingUser->getFieldValue('developerUrl');
                $existingUser->location = $existingUser->getFieldValue('location');
                $existingUser->supportPlan = $existingUser->getFieldValue('supportPlan')->value;
                $existingUser->supportPlanExpiryDate = $existingUser->getFieldValue('supportPlanExpiryDate');
                $existingUser->enablePartnerFeatures = $existingUser->getFieldValue('enablePartnerFeatures');
                $existingUser->enableDeveloperFeatures = $developerIds->contains($existingUser->id);
                $existingUser->isOrg = true;

                $this->stdout("    > Saving user as org ... ");
                if (!Craft::$app->getElements()->saveElement($existingUser)) {
                    throw new Exception("Couldn't save user with id \"$existingUser->id\": " . implode(', ', $existingUser->getFirstErrors()));
                }
                $this->stdout('done' . PHP_EOL);

                if ($existingUser->findOrgAdmins()->exists()) {
                    $this->stdout("    > Org already has admin assigned, skipping.");
                } else {
                    /** @var User|UserBehavior $orgAdmin */
                    $this->stdout("    > Creating admin account ... ");
                    $orgAdmin = Craft::$app->getElements()->duplicateElement($existingUser, [
                        'email' => $email,
                        'username' => $username,
                        'isOrg' => false,
                        'active' => $active,
                        'pending' => $pending,
                    ]);
                    $this->stdout('done' . PHP_EOL);

                    $this->stdout("    > Adding admin user to org ... ");
                    $existingUser->addOrgMember($orgAdmin->id, true);
                    $this->stdout('done' . PHP_EOL);

                    // TODO: Once this exists https://github.com/craftcms/commerce/pull/2801/files
                    $this->stdout("    > Migrating commerce data to org admin ... ");
                    Commerce::getInstance()?->getCustomers()->moveCustomerDataToCustomer($existingUser, $orgAdmin);
                    $this->stdout('done' . PHP_EOL);

                    $this->stdout("    > Relating orders to org ... ");
                    $rows = Order::find()->customer($orgAdmin)->collect()
                        ->map(fn($order) => [
                            $order->id,
                            $existingUser->id,
                        ]);
                    Craft::$app->getDb()->createCommand()
                        ->batchInsert(Table::ORGS_ORDERS, ['id', 'orgId'], $rows->all())
                        ->execute();
                    $this->stdout('done' . PHP_EOL);

                    $this->stdout("Done converting user #$existingUser->id with org admin #$orgAdmin->id" . PHP_EOL . PHP_EOL);
                }

            });

        // TODO: cleanup custom fields

        // TODO: permissions service, canFoo methods
    }
}
