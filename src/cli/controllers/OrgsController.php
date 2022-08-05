<?php

namespace craftnet\cli\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\helpers\StringHelper;
use craftnet\db\Table;
use craftnet\orgs\Org;
use craftnet\partners\Partner;
use craftnet\plugins\Plugin;
use Illuminate\Support\Collection;
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
                $options[] = 'userId';
                break;
        }

        return $options;
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
            ->each(function (User $user) {
                $this->stdout("Creating an org for user #$user->id ($user->email) ..." . PHP_EOL);

                if (Org::find()->ownerId($user->id)->exists()) {
                    $this->stdout("Org already converted, skipping." . PHP_EOL);
                    return;
                }

                $partner = Partner::find()->ownerId($user->id)->status(null)->one();

                $org = new Org();
                $org->title = $partner->businessName ?? $user->developerName ?? $user->username;
                $org->slug = $partner?->websiteSlug ?? $user->username;
                $org->stripeAccessToken = $user->stripeAccessToken;
                $org->stripeAccount = $user->stripeAccount;
                $org->apiToken = $user->apiToken;
                $org->balance = $user->balance ?? 0;
                $org->setOwner($user->id);

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

                // TODO: migrate $partner->locations to an address field (orgs.locationAddressId)

                $this->stdout("    > Saving org ... ");
                if (!Craft::$app->getElements()->saveElement($org)) {
                    throw new Exception("Couldn't save org: " . implode(', ', $org->getFirstErrors()));
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
                    ]);
                Craft::$app->getDb()->createCommand()
                    ->batchInsert(Table::ORGS_ORDERS, ['id', 'orgId', 'purchaserId'], $rows->all())
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
        // drop craftnet_developers
        // drop craftnet_partners
    }
}
