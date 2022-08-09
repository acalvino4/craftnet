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
    public function events(): array
    {
        return [
            ElementQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
        ];
    }

    public function orgId(?int $orgId): OrderQuery
    {
        $this->orgId = $orgId;

        return $this->owner;
    }

    public function beforePrepare(): void
    {
        $this->owner->query->addSelect([
            'orgsOrders.orgId',
            'orgsOrders.approvalPending',
            'orgsOrders.approvalRejected',
        ]);
        $this->owner->query->leftJoin(['orgsOrders' => Table::ORGS_ORDERS], '[[orgsOrders.id]] = [[commerce_orders.id]]');
        $this->owner->subQuery->leftJoin(['orgsOrders' => Table::ORGS_ORDERS], '[[orgsOrders.id]] = [[commerce_orders.id]]');

        if ($this->owner->orgId) {
            $this->owner->subQuery->andWhere(['orgsOrders.orgId' => $this->owner->orgId]);
        }
    }
}
