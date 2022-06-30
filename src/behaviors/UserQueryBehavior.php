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
    public ?bool $orgAdmin = null;
    public ?int $orgMemberOf = null;
    public ?int $hasOrgMember = null;
    public ?int $hasOrgAdmin = null;

    private bool $_joinMembers = false;
    private bool $_joinMembersByOrg = false;

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

        if ($this->orgMemberOf !== null) {
            $this->_joinMembers = true;
            $this->owner->subQuery->andWhere(['orgsMembers.orgId' => $this->orgMemberOf]);
        }

        if ($this->orgAdmin !== null) {
            $this->_joinMembers = true;
            $this->owner->subQuery->andWhere(['orgsMembers.admin' => $this->orgAdmin]);
        }

        if ($this->hasOrgMember !== null) {
            $this->_joinMembersByOrg = true;
            $this->owner->subQuery->andWhere(['orgsMembersByOrg.userId' => $this->hasOrgMember]);
        }

        if ($this->hasOrgAdmin !== null) {
            $this->_joinMembersByOrg = true;
            $this->owner->subQuery->andWhere([
                'orgsMembersByOrg.userId' => $this->hasOrgAdmin,
                'orgsMembersByOrg.admin' => true,
            ]);
        }

        if ($this->_joinMembers) {
            $this->owner->subQuery->innerJoin(['orgsMembers' => Table::ORGS_MEMBERS], '[[orgsMembers.userId]] = [[users.id]]');
        }

        if ($this->_joinMembersByOrg) {
            $this->owner->subQuery->innerJoin(['orgsMembersByOrg' => Table::ORGS_MEMBERS], '[[orgsMembersByOrg.orgId]] = [[users.id]]');
        }
    }

    public static function find(): UserQuery|UserQueryBehavior
    {
        return User::find();
    }
}
