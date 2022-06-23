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
    public ?int $orgMemberOf = null;
    public ?int $hasOrgMember = null;
    public ?int $hasOrgAdmin = null;
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

    public function isOrg(?bool $value): UserQuery|UserQueryBehavior
    {
        $this->isOrg = $value;

        return $this->owner;
    }

    public function hasOrgMember(?int $value): UserQuery|UserQueryBehavior
    {
        $this->hasOrgMember = $value;

        return $this->owner;
    }

    public function hasOrgAdmin(?int $value): UserQuery|UserQueryBehavior
    {
        $this->hasOrgAdmin = $value;

        return $this->owner;
    }

    public function orgMemberOf(?int $value): UserQuery|UserQueryBehavior
    {
        $this->orgMemberOf = $value;

        return $this->owner;
    }

    public function orgAdmin(?bool $value): UserQuery|UserQueryBehavior
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

        if ($this->orgMemberOf !== null || $this->orgAdmin !== null) {
            $this->owner->subQuery->innerJoin(['orgs_members' => Table::ORGS_MEMBERS], '[[orgs_members.userId]] = [[users.id]]');
        }

        if ($this->orgMemberOf !== null) {
            $this->owner->subQuery->andWhere(['orgs_members.orgId' => $this->orgMemberOf]);
        }

        if ($this->orgAdmin !== null) {
            $this->owner->subQuery->andWhere(['orgs_members.admin' => $this->orgAdmin]);
        }

        if ($this->hasOrgMember !== null || $this->hasOrgAdmin !== null) {
            $this->owner->subQuery
                ->innerJoin(['orgs_members' => Table::ORGS_MEMBERS], '[[orgs_members.orgId]] = [[users.id]]')
                ->andWhere(['orgs_members.userId' => $this->hasOrgMember]);
        }

        if ($this->hasOrgAdmin !== null) {
            $this->owner->subQuery->andWhere(['orgs_members.userId' => $this->hasOrgMember]);
        }
    }

    public static function find(): UserQuery|UserQueryBehavior
    {
        return User::find();
    }
}
