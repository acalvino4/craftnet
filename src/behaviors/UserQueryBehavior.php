<?php

namespace craftnet\behaviors;

use craft\elements\db\ElementQuery;
use craft\elements\db\UserQuery;
use craftnet\db\Table;
use yii\base\Behavior;

/**
 * @property UserQuery $owner
 */
class UserQueryBehavior extends Behavior
{
    public ?bool $isOrg = null;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            ElementQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
        ];
    }

    public function isOrg(bool $isOrg): UserQuery
    {
        $this->isOrg = $isOrg;

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
    }
}
