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
            'developers.country',
            'developers.stripeAccessToken',
            'developers.stripeAccount',
            'developers.payPalEmail',
            'developers.apiToken',
        ]);

        $this->owner->query->leftJoin(['developers' => Table::DEVELOPERS], '[[developers.id]] = [[users.id]]');
        $this->owner->subQuery->leftJoin(['developers' => Table::DEVELOPERS], '[[developers.id]] = [[users.id]]');
    }
}
