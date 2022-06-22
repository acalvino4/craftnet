<?php

namespace craftnet\behaviors;

use craft\elements\db\ElementQuery;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craftnet\db\Table;
use yii\base\Behavior;

/**
 * @property UserQuery $owner
 */
class UserQueryBehavior extends Behavior
{
    public ?bool $isOrg = null;
    public ?int $memberOfOrg = null;
    public ?int $hasOrgMember = null;
    public ?bool $orgAdmin = null;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            ElementQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
        ];
    }

    public function isOrg(?bool $value): UserQuery
    {
        $this->isOrg = $value;

        return $this->owner;
    }

    public function hasOrgMember(?int $value): UserQuery
    {
        $this->hasOrgMember = $value;

        return $this->owner;
    }

    public function memberOfOrg(?int $value): UserQuery
    {
        $this->memberOfOrg = $value;

        return $this->owner;
    }

    public function orgAdmin(?bool $value): UserQuery
    {
        $this->orgAdmin = $value;

        return $this->owner;
    }

    /**
     * Prepares the user query.
     */
    public function beforePrepare(): void
    {
        if ($this->owner->select === ['COUNT(*)']) {
            return;
        }

        $this->owner->query->addSelect([
            '(orgs.id is not null) AS isOrg',
            'orgs.country',
            'orgs.stripeAccessToken',
            'orgs.stripeAccount',
            'orgs.payPalEmail',
            'orgs.apiToken',
            'orgs.displayName',
            'orgs.websiteUrl',
            'orgs.websiteSlug',
            'orgs.location',
            'orgs.supportPlan',
            'orgs.supportPlanExpiryDate',
            'orgs.enableDeveloperFeatures',
            'orgs.enablePartnerFeatures',
        ]);

        $this->owner->query->leftJoin(['orgs' => Table::ORGS], '[[orgs.id]] = [[users.id]]');
        $this->owner->subQuery->leftJoin(['orgs' => Table::ORGS], '[[orgs.id]] = [[users.id]]');

        if ($this->isOrg !== null) {
            $this->owner->subQuery->andWhere($this->isOrg ? '[[orgs.id]] is not null' : '[[orgs.id]] is null');
        }

        if ($this->memberOfOrg !== null || $this->orgAdmin !== null) {
            $this->owner->subQuery->innerJoin(['orgs_members' => Table::ORGS_MEMBERS], '[[orgs_members.userId]] = [[users.id]]');
        }

        if ($this->memberOfOrg !== null) {
            $this->owner->subQuery->andWhere(['orgs_members.orgId' => $this->memberOfOrg]);
        }

        if ($this->orgAdmin !== null) {
            $this->owner->subQuery->andWhere(['orgs_members.admin' => $this->orgAdmin]);
        }

        if ($this->hasOrgMember !== null) {
            $this->owner->subQuery
                ->innerJoin(['orgs_members' => Table::ORGS_MEMBERS], '[[orgs_members.orgId]] = [[users.id]]')
                ->andWhere(['orgs_members.userId' => $this->hasOrgMember]);
        }
    }

    public static function find(): UserQuery|UserQueryBehavior
    {
        return User::find();
    }
}
