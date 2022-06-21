<?php

namespace craftnet\behaviors;

use craft\commerce\elements\db\OrderQuery;
use craft\elements\db\ElementQuery;
use craftnet\db\Table;
use yii\base\Behavior;

/**
 * @property OrderQuery $owner
 */
class OrderQueryBehavior extends Behavior
{

    public ?int $orgId = null;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ElementQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
        ];
    }

    public function orgId(int $orgId): OrderQuery
    {
        $this->orgId = $orgId;

        return $this->owner;
    }

    public function beforePrepare()
    {
        if (!$this->owner->orgId) {
            return;
        }

        $this->owner->subQuery->innerJoin(['orgs_orders' => Table::ORGS_ORDERS], '[[orgs_orders.id]] = [[commerce_orders.id]]');
        $this->owner->subQuery->andWhere(['orgs_orders.orgId' => $this->owner->orgId]);
    }
}
