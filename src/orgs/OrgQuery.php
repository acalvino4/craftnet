<?php

namespace craftnet\orgs;

use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\Db;
use craftnet\db\Table;
use Illuminate\Support\Collection;
use ReflectionObject;
use ReflectionProperty;
use yii\db\Connection;

class OrgQuery extends ElementQuery
{
    public ?string $stripeAccessToken = null;
    public ?string $stripeAccount = null;
    public ?string $apiToken = null;
    public ?int $balance = null;
    public ?int $creatorId = null;
    public ?int $paymentSourceId = null;
    public ?int $billingAddressId = null;
    private ?int $hasMemberId = null;
    private ?int $hasOwnerId = null;
    private bool $joinMembers = false;

    public function creatorId(?int $creatorId): OrgQuery
    {
        $this->creatorId = $creatorId;
        return $this;
    }

    public function paymentSourceId(?int $paymentSourceId): OrgQuery
    {
        $this->paymentSourceId = $creatorId;
        return $this;
    }

    public function billingAddressId(?int $billingAddressId): OrgQuery
    {
        $this->billingAddressId = $creatorId;
        return $this;
    }

    public function hasMember(null|int|User $value): static
    {
        $this->joinMembers = true;
        $this->hasMemberId = $value instanceof User ? $value->id : $value;

        return $this;
    }

    public function hasOwner(null|int|User $value): static
    {
        $this->joinMembers = true;
        $this->hasOwnerId = $value instanceof User ? $value->id : $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('craftnet_orgs');
        $columns = Collection::make([
            'stripeAccessToken',
            'stripeAccount',
            'apiToken',
            'balance',
            'creatorId',
            'paymentSourceId',
            'billingAddressId',
        ]);

        $this->query->select(
            $columns->map(fn($column) => sprintf('%s.%s', Table::ORGS, $column))->all()
        );

        $columns->each(function($column) {
            if (isset($this->$column)) {
                $this->subQuery->andWhere(
                    Db::parseParam(sprintf('%s.%s', Table::ORGS, $column), $this->$column)
                );
            }
        });

        if ($this->joinMembers) {
            $this->subQuery->innerJoin(['orgsMembers' => Table::ORGS_MEMBERS], '[[orgsMembers.orgId]] = [[elements.id]]');
        }
        if ($this->hasMemberId !== null) {
            $this->subQuery->andWhere(['orgsMembers.userId' => $this->hasMemberId]);
        }
        if ($this->hasOwnerId !== null) {
            $this->subQuery->andWhere([
                'orgsMembers.userId' => $this->hasOwnerId,
                'orgsMembers.owner' => true,
            ]);
        }

        return parent::beforePrepare();
    }
}
