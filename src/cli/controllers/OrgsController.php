<?php

namespace craftnet\cli\controllers;

use Craft;
use craft\commerce\behaviors\CustomerBehavior;
use craft\commerce\elements\Order;
use craft\elements\Address;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craftnet\db\Table;
use craftnet\orgs\Org;
use craftnet\partners\Partner;
use craftnet\plugins\Plugin;
use craftnet\records\PaymentMethod;
use Illuminate\Support\Collection;
use nystudio107\retour\Retour;
use Throwable;
use yii\console\Controller;
use yii\db\Exception;

class OrgsController extends Controller
{
    public ?int $userId = null;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'convert':
            case 'create-payment-methods':
                $options[] = 'userId';
                break;
        }

        return $options;
    }

    public function actionCreatePaymentMethods(): void
    {
        $this->stdout("Creating payment methods for users ... " . PHP_EOL);

        User::find()->id($this->userId)->collect()
            ->each(function(User|CustomerBehavior $user) {
                $this->stdout("    > Creating payment method for $user ... ");

                if (!$user->primaryPaymentSourceId) {
                    $this->stdout("No payment source, skipping." . PHP_EOL);
                    return;
                }

                $paymentMethod = new PaymentMethod();
                $paymentMethod->paymentSourceId = $user->primaryPaymentSourceId;
                $paymentMethod->billingAddressId = $user->primaryBillingAddressId;
                $paymentMethod->ownerId = $user->id;

                if (!$paymentMethod->save(false)) {
                    throw new Exception("Couldn't save payment method: " . implode(', ', $paymentMethod->getFirstErrors()));
                }

                $this->stdout('done' . PHP_EOL);
            });

        $this->stdout('Done creating payment methods' . PHP_EOL);
    }

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
        $this->run('create-payment-methods');

        $partnerOwnerIds = Partner::find()->collect()->pluck('ownerId');
        $developerIds = Plugin::find()->collect()->pluck('developerId');

        $userIds = $this->userId ?? $developerIds
            ->push(...$partnerOwnerIds)
            ->unique()
            ->all();

        User::find()
            ->status('credentialed')
            ->id($userIds)
            ->collect()
            ->each(function (User|CustomerBehavior $user) {
                $this->stdout("Creating an org for user #$user->id ($user->email) ..." . PHP_EOL);

                if (Org::find()->ownerId($user->id)->exists()) {
                    $this->stdout("Org already converted, skipping." . PHP_EOL);
                    return;
                }

                $partner = Partner::find()->ownerId($user->id)->status(null)->one();

                $org = new Org();
                $org->creatorId = $user->id;
                $org->title = $partner->businessName ?? $user->developerName ?? $user->username;
                $org->slug = $partner?->websiteSlug ?? $user->username;
                $org->stripeAccessToken = $user->stripeAccessToken;
                $org->stripeAccount = $user->stripeAccount;
                $org->apiToken = $user->apiToken;
                $org->balance = $user->balance ?? 0;
                $org->setOwner($user->id);
                $org->paymentMethodId = PaymentMethod::findOne([
                    'ownerId' => $user->id,
                    'paymentSourceId' => $user->primaryPaymentSourceId,
                ])?->id;
                $projectsAsMatrix = Collection::make($partner?->getProjects())
                    ->flatMap(function($project, $index) {
                        $key = 'new' . ($index + 1);

                        return [
                            $key => [
                                'type' => 'project',
                                'fields' => [
                                    'projectName' => $project->name,
                                    'projectUrl' => $project->url,
                                    'linkType' => $project->linkType,
                                    'role' => $project->role,
                                    'withCraftCommerce' => $project->withCraftCommerce,
                                    'screenshots' => $project->getScreenshotIds(),
                                ]
                            ]
                        ];
                    })
                    ->all();

                $org->setFieldValues([
                    'externalUrl' => $partner?->website ?? $user->developerUrl,
                    'location' => $user->location,
                    'payPalEmail' => $user->payPalEmail,
                    'enablePartnerFeatures' => $user->enablePartnerFeatures,
                    'orgLogo' => array_filter([$partner?->logoAssetId ?? $user->photoId]),
                    'primaryContactName' => $partner?->primaryContactName,
                    'primaryContactEmail' => $partner?->primaryContactEmail,
                    'primaryContactPhone' => $partner?->primaryContactPhone,
                    'partnerFullBio' => $partner?->fullBio,
                    'partnerShortBio' => $partner?->shortBio,
                    'partnerHasFullTimeDev' => $partner?->hasFullTimeDev,
                    'partnerIsCraftVerified' => $partner?->isCraftVerified,
                    'partnerIsCommerceVerified' => $partner?->isCommerceVerified,
                    'partnerIsEnterpriseVerified' => $partner?->isEnterpriseVerified,
                    'partnerIsRegisteredBusiness' => $partner?->isRegisteredBusiness,
                    'partnerAgencySize' => $partner?->agencySize,
                    'partnerCapabilities' => Collection::make($partner?->getCapabilities())
                        ->map(fn($label) => StringHelper::toCamelCase($label))->all(),
                    'partnerExpertise' => $partner?->expertise,
                    'partnerVerificationStartDate' => $partner?->getVerificationStartDate(),
                    'partnerRegion' => $partner?->region ? StringHelper::toCamelCase($partner?->region) : null,
                    'partnerProjects' => $projectsAsMatrix,
                ]);

                $this->stdout("    > Saving org ... ");
                if (!Craft::$app->getElements()->saveElement($org)) {
                    throw new Exception("Couldn't save org: " . implode(', ', $org->getFirstErrors()));
                }
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Adding partner address as address element ... ");
                if ($legacyLocation = $partner?->getLocations()[0] ?? null) {
                    $location = new Address();
                    $location->ownerId = $org->id;
                    $location->title = $legacyLocation->title;
                    $location->fullName = $partner->primaryContactName;
                    $location->countryCode = $legacyLocation->country;
                    $location->administrativeArea = $legacyLocation->state;
                    $location->locality = $legacyLocation->city;
                    $location->postalCode = $legacyLocation->zip;
                    $location->addressLine1 = $legacyLocation->addressLine1 ?? null;
                    $location->addressLine2 = $legacyLocation->addressLine2 ?? null;
                    $location->organization = $org->title;
                    $location->organizationTaxId = $user->businessVatId ?? null;
                    $location->addressPhone = $legacyLocation->phone ?? null;
                    $location->addressAttention = $legacyLocation->attention ?? null;

                    // Not validating because these addresses won't validate until normalized
                    if (Craft::$app->getElements()->saveElement($location, false)) {
                        $org->locationAddressId = $location->id;
                        Craft::$app->getElements()->saveElement($org);
                    }
                }
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Removing avatar from user ... ");
                $user->setPhoto(null);
                Craft::$app->getElements()->saveElement($user);
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Relating orders to org ... ");
                $rows = Order::find()->customer($user)->collect()
                    ->map(fn($order) => [
                        $order->id,
                        $org->id,
                        $order->customerId,
                        $order->customerId,
                    ]);
                Craft::$app->getDb()->createCommand()
                    ->batchInsert(Table::ORGS_ORDERS, ['id', 'orgId', 'creatorId', 'purchaserId'], $rows->all())
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

                $this->stdout("    > Deleting redundant addresses ... ");
                $usedAddressIds = array_filter([$org?->paymentMethod?->billingAddressId, $org->locationAddressId]);
                $idParam = $usedAddressIds ? array_merge([
                    'not',
                ], $usedAddressIds) : null;
                Address::find()
                    ->ownerId($user->id)
                    ->id($idParam)
                    ->collect()
                    ->each(function ($address) {
                        return Craft::$app->getElements()->deleteElementById($address->id, hardDelete: true);
                    });
                $this->stdout('done' . PHP_EOL);

                $this->stdout("    > Creating Retour redirects ... ");
                Retour::getInstance()->redirects->saveRedirect([
                    'redirectMatchType' => 'exactmatch',
                    'redirectHttpCode' => 301,
                    'redirectSrcUrl' => UrlHelper::siteUrl(
                        "developer/$user->id",
                        siteId: Craft::$app->getSites()->getSiteByHandle('plugins')->id,
                    ),
                    'redirectDestUrl' => $org->url,
                ]);
                $this->stdout('done' . PHP_EOL);

                $this->stdout("Done creating org #$org->id" . PHP_EOL . PHP_EOL);
            });
    }

    public function actionCleanup(): void
    {
        // drop craftnet_developers
        // drop craftnet_partners
    }
}
