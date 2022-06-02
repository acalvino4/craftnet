<?php

namespace craftnet\console\controllers;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\db\Table;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\helpers\Console;
use craftnet\behaviors\UserBehavior;
use craftnet\orgs\Org;
use craftnet\partners\Partner;
use craftnet\plugins\Plugin;
use yii\db\Exception;

class OrgsController extends \yii\console\Controller
{
    /**
     * Converts existing developers and parters to orgs and creates an org admin with matching credentials
     *
     * @return void
     * @throws ElementNotFoundException
     * @throws Exception
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
            // TODO: replace with 'credentialed'
            ->status(['active', 'pending'])
            ->id($existingUserIds)
            ->collect()
            ->each(function(User $existingUser) use($developerIds) {
                /** @var User|UserBehavior $existingUser */
                $email = $existingUser->email;
                $username = $existingUser->username;
                $active = $existingUser->active;
                $pending = $existingUser->pending;

                $this->stdout("Converting user #{$existingUser->id} ({$existingUser->email}) to org ..." . PHP_EOL);

                if (!Craft::$app->getUsers()->removeCredentials($existingUser)) {
                    throw new Exception("Couldn't remove credentials: " . implode(', ', $existingUser->getFirstErrors()));
                }

                // Save w/o user/email so new admin user can validate.
                $partner = Partner::find()->ownerId($existingUser->id)->one();
                $billingAddress = array_filter([
                    'businessName' => $existingUser->getFieldValue('businessName'),
                    'address1' => $existingUser->getFieldValue('businessAddressLine1'),
                    'address2' => $existingUser->getFieldValue('businessAddressLine2'),
                    'city' => $existingUser->getFieldValue('businessCity'),
                    'country' => $existingUser->getFieldValue('businessCountry'),
                    'state' => $existingUser->getFieldValue('businessState'),
                    'zipCode' => $existingUser->getFieldValue('businessZipCode'),
                ]);

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
                $existingUser->billingAddress = count($billingAddress) ? $billingAddress : null;
                $existingUser->vatId = $existingUser->getFieldValue('businessVatId');
                $existingUser->org = new Org($existingUser);

                $this->stdout("    > Saving user as org ... ");
                if (!Craft::$app->getElements()->saveElement($existingUser)) {
                    throw new Exception("Couldn't save user with id \"{$existingUser->id}\": " . implode(', ', $existingUser->getFirstErrors()));
                }
                $this->stdout('done' . PHP_EOL);

                if ($existingUser->getOrg()->getAdminIds()) {
                    $this->stdout("    > Org already has admin assigned, skipping.");
                } else {
                    /** @var User|UserBehavior $orgAdmin */
                    $this->stdout("    > Creating admin account ... ");
                    $orgAdmin = Craft::$app->getElements()->duplicateElement($existingUser, [
                        'email' => $email,
                        'username' => $username,
                        'org' => null,
                        'active' => $active,
                        'pending' => $pending,
                    ]);
                    $this->stdout('done' . PHP_EOL);

                    $this->stdout("    > Adding admin user to org ... ");
                    $existingUser->getOrg()->addAdmin($orgAdmin);
                    $this->stdout('done' . PHP_EOL);

                    // TODO: Once this exists https://github.com/craftcms/commerce/pull/2801/files
                    // $this->stdout("    > Migrating commerce data to org admin ... ");
                    // Commerce::getInstance()->getCustomers()->moveCustomerDataToCustomer($existingUser, $orgAdmin);
                    // $this->stdout('done' . PHP_EOL);

                    $this->stdout("    > Migrating address data to org admin ... ");
                    Craft::$app->getDb()->createCommand()
                        ->update(Table::ADDRESSES, [
                            'ownerId' => $orgAdmin->id,
                        ], [
                            'ownerId' => $existingUser->id,
                        ])
                        ->execute();
                    $this->stdout('done' . PHP_EOL);

                    $this->stdout("Done converting user #{$existingUser->id} with org admin #{$orgAdmin->id}" . PHP_EOL . PHP_EOL);
                }

            });

        // TODO: cleanup custom fields

        // TODO: permissions service, canFoo methods
    }
}
