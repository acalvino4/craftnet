<?php

namespace craftnet\orgs;

use Craft;
use craft\base\Element;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\Site;
use craftnet\behaviors\UserQueryBehavior;
use craftnet\db\Table;
use Illuminate\Support\Collection;
use yii\base\InvalidConfigException;
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

    public function init(): void
    {
        $this->siteId = Craft::$app->getSites()->getSiteByHandle('plugins')->id;
        parent::init();
    }

    public static function displayName(): string
    {
        return Craft::t('app', 'Organization');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Organizations');
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat(): ?string
    {
        return 'developer/{slug}';
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

    public static function isLocalized(): bool
    {
        return true;
    }

    public function getSupportedSites(): array
    {
        return [$this->siteId];
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
                'criteria' => [],
                'label' => Craft::t('app', 'All {pluralLowerDisplayName}', [
                    'pluralLowerDisplayName' => static::pluralLowerDisplayName()
                ]),
            ],
            [
                'key' => 'partners',
                'criteria' => ['enablePartnerFeatures' => true],
                'label' => Craft::t('app', 'Partners'),
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'link'
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
                        // TODO: Include Address field (orgs.locationAddressId)
                        // TODO: Include Address field (orgs.billingAddressId)
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
