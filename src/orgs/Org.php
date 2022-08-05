<?php

namespace craftnet\orgs;

use Craft;
use craft\base\Element;
use craft\commerce\models\PaymentSource;
use craft\commerce\Plugin as Commerce;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craftnet\behaviors\UserQueryBehavior;
use craftnet\db\Table;
use craftnet\plugins\Plugin;
use DateTime;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\db\Exception;
use yii\db\StaleObjectException;

/**
 *
 * @property-read array $invitations
 * @property-read null|PaymentSource $paymentSource
 * @property-read null|string $invitationUrl
 */
class Org extends Element
{
    public ?int $ownerId = null;
    public ?string $stripeAccessToken = null;
    public ?string $stripeAccount = null;
    public ?string $apiToken = null;
    public float $balance = 0;
    public ?int $paymentSourceId = null;
    public ?int $billingAddressId = null;
    public ?int $locationAddressId = null;

    /**
     * @var Plugin[]|null
     */
    private ?array $_plugins = null;

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

    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @throws \yii\base\Exception
     */
    public function getInvitationUrl(): ?string
    {
        return match($this->site->handle) {
            'console' => UrlHelper::siteUrl("$this->uri/invitation"),
            default => null,
        };
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat(): ?string
    {
        return match($this->site->handle) {
            'plugins' => 'developer/{slug}',
            'console' => 'orgs/{slug}',
            default => null,
        };
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
        $titleElement = new TitleField();
        $titleElement->translatable = false;

        return new FieldLayout([
            'type' => static::class,
            'tabs' => [
                [
                    'name' => 'Organization',
                    'elements' => [
                        $titleElement,
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

    public function getInvitation(User $user): ?InvitationRecord
    {
        return InvitationRecord::find()
            ->where([
                'orgId' => $this->id,
                'userId' => $user->id,
            ])
            ->andWhere(['>', 'expiryDate', Db::prepareDateForDb(new DateTime())])
            ->one();
    }

    /**
     * @throws StaleObjectException
     */
    public function deleteInvitation(User $user): bool
    {
        $invitationRecord = InvitationRecord::findOne([
            'orgId' => $this->id,
            'userId' => $user->id,
        ]);

        return (bool) $invitationRecord?->delete();
    }

    public function getInvitations(): array
    {
        return InvitationRecord::find()->where([
            'orgId' => $this->id,
        ])->all();
    }

    /**
     * @throws \yii\base\UserException
     */
    public function createInvitation(User $user, bool $admin = false, ?DateTime $expiryDate = null): bool
    {
        if ($this->getInvitation($user)) {
            throw new UserException('User already has an existing invitation to this organization.');
        }

        if (!$expiryDate) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->defaultTokenDuration);
            $expiryDate = DateTimeHelper::currentUTCDateTime();
            $expiryDate->add($interval);
        }

        $invitationRecord = new InvitationRecord();
        $invitationRecord->orgId = $this->id;
        $invitationRecord->userId = $user->id;
        $invitationRecord->admin = $admin;

        $invitationRecord->expiryDate = Db::prepareDateForDb($expiryDate);

        return $invitationRecord->save();
    }

    /**
     * @throws UserException
     */
    public function getMemberRole(User $user): string
    {
        if (!$this->hasMember($user)) {
            throw new UserException('User is not a member of this organization.');
        }

        if ($this->hasOwner($user)) {
            return MemberRoleEnum::Owner()->value;
        }

        if ($this->hasAdmin($user)) {
            return MemberRoleEnum::Admin()->value;
        }

        return MemberRoleEnum::Member()->value;
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
     * @throws UserException
     */
    public function addMember(User $user, array $attributes = []): bool
    {
        if ($this->hasMember($user)) {
            throw new UserException('User is already a member of this organization.');
        }

        return (bool) Craft::$app->getDb()->createCommand()
            ->insert(Table::ORGS_MEMBERS, [
                'orgId' => $this->id,
                'userId' => $user->id,
            ] + $attributes)
            ->execute();
    }

    /**
     * @throws Exception
     * @throws UserException
     */
    public function setMemberRole(User $user, string|MemberRoleEnum $role): bool
    {
        $role = is_string($role) ? MemberRoleEnum::tryFrom($role) : $role;

        if (!$role) {
            throw new UserException('Invalid role.');
        }

        $admin = $role === MemberRoleEnum::Admin();

        if ($role === MemberRoleEnum::Owner()) {
            // TODO: transfer ownership
            throw new UserException('Nope…');
        }

        if (!$admin && $this->hasOwner($user)) {
            throw new UserException('Organization owners must have at admin privileges.');
        }

        return (bool) Craft::$app->getDb()->createCommand()
            ->update(Table::ORGS_MEMBERS, ['admin' => $admin], [
                'orgId' => $this->id,
                'userId' => $user->id,
            ])
            ->execute();
    }

    /**
     * @throws Exception
     * @throws UserException
     */
    public function addAdmin(User $user, array $attributes = []): bool
    {
        return $this->addMember($user, [
            'admin' => true
        ] + $attributes);
    }

    /**
     * @throws Exception
     * @throws UserException
     */
    public function removeMember(User $user): bool
    {
        if ($this->hasOwner($user)) {
            throw new UserException('Organization owners cannot be removed.');
        }

        return (bool)Craft::$app->getDb()->createCommand()
            ->delete(Table::ORGS_MEMBERS, [
                'orgId' => $this->id,
                'userId' => $user->id,
            ])
            ->execute();
    }

    public function getOwner(): ?User
    {
        return $this->ownerId ? Craft::$app->getUsers()->getUserById($this->ownerId) : null;
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins(): array
    {
        if ($this->_plugins !== null) {
            return $this->_plugins;
        }

        /** @var Plugin[] $plugins */
        $plugins = Plugin::find()
            ->developerId($this->id)
            ->status(null)
            ->all();

        return $this->_plugins = $plugins;
    }

    public function getPaymentSource(): ?PaymentSource
    {
        if (!$this->paymentSourceId) {
            return null;
        }

        return Commerce::getInstance()->getPaymentSources()->getPaymentSourceById($this->paymentSourceId);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function beforeSave(bool $isNew): bool
    {
        if (!$this->ownerId) {
            throw new InvalidConfigException('No owner ID assigned to the Organization.');
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @throws Exception
     * @throws UserException
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
            'ownerId',
        ]);

        Db::upsert(Table::ORGS, $data);

        if ($isNew) {
            $this->addAdmin($this->getOwner());
        }
    }

    /**
     * @throws Exception
     */
    public function afterDelete(): void
    {
        parent::afterDelete();
        Db::delete(Table::ORGS, ['id' => $this->id]);
    }

    public function hasMember(User $user): bool
    {
        /** @var UserQuery|UserQueryBehavior $query */
        $query = User::find()->id($user->id);
        return $query->orgMember(true)->ofOrg($this)->exists();
    }

    public function hasAdmin(User $user): bool
    {
        /** @var UserQuery|UserQueryBehavior $query */
        $query = User::find()->id($user->id);
        return $query->orgAdmin(true)->ofOrg($this)->exists();
    }

    public function hasOwner(User $user): bool
    {
        return $user->id === $this->ownerId;
    }

    public function canManageMembers(User $user): bool
    {
        return $user->admin || $this->hasOwner($user) || $this->hasAdmin($user);
    }

    public function canView(User $user): bool
    {
        return $user->admin || $this->hasOwner($user) || $this->hasMember($user);
    }

    public function canSave(User $user): bool
    {
        return !$this->id || $user->admin || $this->hasOwner($user) || $this->hasAdmin($user);
    }

    public function canCreateDrafts(User $user): bool
    {
        return $user->admin;
    }

    public function canDelete(User $user): bool
    {
        return $user->admin;
    }

    public function canDuplicate(User $user): bool
    {
        return $user->admin;
    }
}
