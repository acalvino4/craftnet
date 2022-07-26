<?php

namespace craftnet\orgs;

use Craft;
use craft\base\Element;
use craft\elements\Address;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\fieldlayoutelements\addresses\AddressField;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\TitleField;
use craft\fields\RadioButtons;
use craft\fields\Url;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\services\ElementSources;
use craftnet\behaviors\UserQueryBehavior;
use craftnet\db\Table;
use DateTime;
use yii\base\UserException;
use yii\db\Exception;

class Org extends Element
{
    public ?string $stripeAccessToken = null;
    public ?string $stripeAccount = null;
    public ?string $apiToken = null;
    public float $balance = 0;
    public ?int $creatorId = null;
    public ?int $paymentSourceId = null;
    public ?int $billingAddressId = null;

    public static function displayName(): string
    {
        return Craft::t('app', 'Organization');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Organizations');
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): OrgQuery
    {
        return new OrgQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'criteria' => ['status' => null],
                'label' => Craft::t('app', 'All {pluralLowerDisplayName}', [
                    'pluralLowerDisplayName' => static::pluralLowerDisplayName()
                ]),
            ],
        ];
    }

    public function getFieldLayout(): ?FieldLayout
    {
        return new FieldLayout([
            'type' => static::class,
            'tabs' => [
                [
                    'name' => 'Organization',
                    'elements' => [
                        new TitleField(),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('requireOrderApproval')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('externalUrl')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('location')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('payPalEmail')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('orgLogo')
                        ),
                    ]
                ],
                [
                    'name' => 'Partner Network',
                    'elements' => [
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('enablePartnerFeatures')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('primaryContactName')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('primaryContactEmail')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('primaryContactPhone')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerFullBio')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerShortBio')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerHasFullTimeDev')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerIsCraftVerified')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerIsCommerceVerified')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerIsEnterpriseVerified')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerIsRegisteredBusiness')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerAgencySize')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerCapabilities')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerExpertise')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerVerificationStartDate')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerRegion')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('partnerProjects')
                        ),
                    ]
                ]
            ]
        ]);
    }

    protected function cpEditUrl(): ?string
    {
        $path = sprintf('orgs/%s', $this->getCanonicalId());

        return UrlHelper::cpUrl($path);
    }

    public function metaFieldsHtml(bool $static): string
    {
        $fields = [
            $this->slugFieldHtml($static),
            parent::metaFieldsHtml($static)
        ];

        return implode("\n", $fields);
    }

    /**
     * @throws Exception
     */
    public function addMember(int|User $user, array $insertColumns = []): void
    {
        Craft::$app->getDb()->createCommand()
            ->upsert(Table::ORGS_MEMBERS, [
                'orgId' => $this->id,
                'userId' => $user->id,
            ] + $insertColumns)
            ->execute();
    }

    /**
     * @throws Exception
     */
    public function addOwner($user): void
    {
        $this->addMember($user, [
            'owner' => true
        ]);
    }

    /**
     * @throws Exception
     * @throws UserException
     */
    public function removeMember(int|User $user): void
    {
        if ($this->findOwners()->count() === 1) {
            throw new UserException('Organizations must have at least one owner.');
        }

        Craft::$app->getDb()->createCommand()
            ->delete(Table::ORGS_MEMBERS, [
                'orgId' => $this->id,
                'userId' => $user instanceof User ? $user->id : $user,
            ])
            ->execute();
    }

    public function findOwners(): UserQuery
    {
        /** @var UserQuery|UserQueryBehavior $query */
        $query = User::find();
        return $query->ofOrg($this)->orgOwner(true);
    }

    /**
     * @throws Exception
     */
    public function afterSave(bool $isNew): void
    {
        parent::afterSave($isNew);
        $data = $this->getAttributes([
            'id',
            'stripeAccessToken',
            'stripeAccount',
            'apiToken',
            'balance',
            'creatorId',
        ]);

        Db::upsert(Table::ORGS, $data);
    }

    /**
     * @throws Exception
     */
    public function afterDelete(): void
    {
        parent::afterDelete();
        Db::delete(Table::ORGS, ['id' => $this->id]);
    }

    public function canView(User $user): bool
    {
        if ($user->admin) {
            return true;
        }

        /** @var UserQuery|UserQueryBehavior $query */
        $query = User::find()->id($user->id);
        return $query->orgMember(true)->ofOrg($this)->exists();
    }

    public function canSave(User $user): bool
    {
        if ($user->admin) {
            return true;
        }

        /** @var UserQuery|UserQueryBehavior $query */
        $query = User::find()->id($user->id);
        return $query->orgOwner(true)->ofOrg($this)->exists();
    }

    public function canCreateDrafts(User $user): bool
    {
        if ($user->admin) {
            return true;
        }

        return false;
    }

    public function canDelete(User $user): bool
    {
        if ($user->admin) {
            return true;
        }

        return false;
    }

    public function canDuplicate(User $user): bool
    {
        if ($user->admin) {
            return true;
        }

        return false;
    }
}
