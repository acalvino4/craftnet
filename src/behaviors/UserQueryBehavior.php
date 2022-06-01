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
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ElementQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
        ];
    }

    /**
     * Prepares the user query.
     */
    public function beforePrepare()
    {
        if ($this->owner->select === ['COUNT(*)']) {
            return;
        }

        $this->owner->query->addSelect([
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
            'orgs.billingAddress',
            'orgs.vatId',
        ]);

        $this->owner->query->leftJoin(['orgs' => Table::ORGS], '[[orgs.id]] = [[users.id]]');
        $this->owner->subQuery->leftJoin(['orgs' => Table::ORGS], '[[orgs.id]] = [[users.id]]');
    }
}
