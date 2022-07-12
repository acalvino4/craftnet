<?php

namespace craftnet\orgs;

use Craft;
use craft\base\Element;
use craft\elements\Address;
use craft\elements\db\ElementQueryInterface;
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
use craftnet\db\Table;
use DateTime;
use yii\db\Exception;

class Org extends Element
{
    public ?string $stripeAccessToken = null;
    public ?string $stripeAccount = null;
    public ?string $apiToken = null;
    public int $balance = 0;
    public ?int $creatorId = null;

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

    public static function find(): ElementQueryInterface
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
                            Craft::$app->getFields()->getFieldByHandle('externalUrl')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('location')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('payPalEmail')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('requireOrderApproval')
                        ),
                        new CustomField(
                            Craft::$app->getFields()->getFieldByHandle('enablePartnerFeatures')
                        ),
                        new AddressField(),
                    ]
                ],
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
     */
    public function removeMember(int|User $user): void
    {
        Craft::$app->getDb()->createCommand()
            ->delete(Table::ORGS_MEMBERS, [
                'orgId' => $this->id,
                'userId' => $user instanceof User ? $user->id : $user,
            ])
            ->execute();
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
        return true;
    }

    public function canSave(User $user): bool
    {
        return true;
    }

    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    public function canDelete(User $user): bool
    {
        return true;
    }

    public function canDuplicate(User $user): bool
    {
        return true;
    }
}
