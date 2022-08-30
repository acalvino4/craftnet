<?php

namespace craftnet\behaviors;

use craft\commerce\elements\db\OrderQuery;
use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\Db;
use craftnet\db\Table;
use yii\base\Behavior;

/**
 * @property OrderQuery $owner
 */
class OrderQueryBehavior extends Behavior
{
    public bool $withOrgOrders = true;
    public ?int $orgId = null;
    public ?int $creatorId = null;
    public ?int $purchaserId = null;
    public ?bool $approvalRequested = null;
    private ?bool $approvalPending = null;
    private ?int $approvalRejectedById = null;
    private ?int $approvalRequestedById = null;
    private mixed $approvalRejectedDate = null;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            ElementQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
        ];
    }

    /**
     * @param bool $withOrgOrders
     * @return OrderQueryBehavior
     */
    public function withOrgOrders(bool $withOrgOrders): OrderQueryBehavior
    {
        $this->withOrgOrders = $withOrgOrders;
        return $this;
    }

    public function orgId(?int $orgId): OrderQuery
    {
        $this->orgId = $orgId;

        if ($this->orgId) {
            $this->withOrgOrders = true;
        }

        return $this->owner;
    }

    public function approvalPending(bool $approvalPending): OrderQuery
    {
        $this->approvalPending = $approvalPending;

        if ($this->approvalPending) {
            $this->withOrgOrders = true;
            $this->approvalRequested = true;
        }

        return $this->owner;
    }

    public function approvalRequested(bool $approvalRequested): OrderQuery
    {
        $this->approvalRequested = $approvalRequested;

        if ($this->approvalRequested) {
            $this->withOrgOrders = true;
        }

        return $this->owner;
    }

    public function approvalRejectedBy(int|User|null $approvalRejectedBy): OrderQuery
    {
        $approvalRejectedById = $approvalRejectedBy instanceof User ? $approvalRejectedBy->id : $approvalRejectedBy;
        $this->approvalRejectedById = $approvalRejectedById;

        if ($this->approvalRejectedById) {
            $this->approvalRequested = true;
            $this->withOrgOrders = true;
        }

        return $this->owner;
    }

    public function approvalRequestedBy(int|User|null $approvalRequestedBy): OrderQuery
    {
        $approvalRequestedById = $approvalRequestedBy instanceof User ? $approvalRequestedBy->id : $approvalRequestedBy;
        $this->approvalRequestedById = $approvalRequestedById;

        if ($this->approvalRequestedById) {
            $this->approvalRequested = true;
            $this->withOrgOrders = true;
        }

        return $this->owner;
    }

    public function approvalRejectedDate(mixed $approvalRejectedDate): OrderQuery
    {
        $this->approvalRejectedDate = $approvalRejectedDate;

        if ($this->approvalRejectedDate) {
            $this->approvalRequested = true;
            $this->withOrgOrders = true;
        }

        return $this->owner;
    }

    public function beforePrepare(): void
    {
        $this->owner->query->addSelect([
            'orgsOrders.orgId',
            'orgsOrders.creatorId',
            'orgsOrders.purchaserId',
            'orgsOrderApprovals.orgId AS approvalRequestedForOrgId',
            'orgsOrderApprovals.requestedById AS approvalRequestedById',
            'orgsOrderApprovals.rejectedById AS approvalRejectedById',
            'orgsOrderApprovals.dateRejected AS approvalRejectedDate',
        ]);
        $this->owner->query->leftJoin(['orgsOrders' => Table::ORGS_ORDERS], '[[orgsOrders.id]] = [[commerce_orders.id]]');
        $this->owner->subQuery->leftJoin(['orgsOrders' => Table::ORGS_ORDERS], '[[orgsOrders.id]] = [[commerce_orders.id]]');

        $this->owner->query->leftJoin(['orgsOrderApprovals' => Table::ORGS_ORDERAPPROVALS], '[[orgsOrderApprovals.orderId]] = [[commerce_orders.id]]');
        $this->owner->subQuery->leftJoin(['orgsOrderApprovals' => Table::ORGS_ORDERAPPROVALS], '[[orgsOrderApprovals.orderId]] = [[commerce_orders.id]]');

        if ($this->orgId) {
            if ($this->approvalRequested) {
                $this->owner->subQuery->orWhere(['orgsOrderApprovals.orgId' => $this->orgId]);
            } else {
                $this->owner->subQuery->andWhere(['orgsOrders.orgId' => $this->orgId]);
            }
        }

        if (!$this->withOrgOrders) {
            $this->owner->subQuery->andWhere(['orgsOrders.orgId' => null]);
        }

        if ($this->creatorId) {
            $this->owner->subQuery->andWhere(['orgsOrders.creatorId' => $this->creatorId]);
        }

        if ($this->purchaserId) {
            $this->owner->subQuery->andWhere(['orgsOrders.purchaserId' => $this->purchaserId]);
        }

        if ($this->approvalRequested) {
            $this->owner->subQuery->andWhere(['not', ['orgsOrderApprovals.requestedById' => null]]);
        }

        if ($this->approvalPending) {
            $this->owner->subQuery->andWhere(['orgsOrderApprovals.rejectedById' => null]);
        }

        if ($this->approvalRequestedById) {
            $this->owner->subQuery->andWhere(['orgsOrderApprovals.requestedById' => $this->approvalRequestedById]);
        }

        if ($this->approvalRejectedById) {
            $this->owner->subQuery->andWhere(['orgsOrderApprovals.rejectedById' => $this->approvalRejectedById]);
        }

        if ($this->approvalRejectedDate) {
            $this->owner->subQuery->andWhere(Db::parseDateParam('orgsOrderApprovals.rejectedDate', $this->approvalRejectedDate));
        }
    }
}
