<?php

namespace craftnet\orgs;

use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\Db;
use craftnet\db\Table;
use Illuminate\Support\Collection;

class OrgQuery extends ElementQuery
{
    public ?string $stripeAccessToken = null;
    public ?string $stripeAccount = null;
    public ?string $apiToken = null;
    public ?int $balance = null;
    public ?int $ownerId = null;
    public int|array|null $paymentMethodId = null;
    public ?int $locationAddressId = null;
    public ?int $orderId = null;
    private ?int $hasMemberId = null;
    private ?int $hasAdminId = null;
    private bool $joinMembers = false;

    public function orderId(?int $orderId): OrgQuery
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function ownerId(?int $ownerId): OrgQuery
    {
        $this->ownerId = $ownerId;
        return $this;
    }

    public function paymentMethodId(int|array|null $paymentMethodId): OrgQuery
    {
        $this->paymentMethodId = $paymentMethodId;
        return $this;
    }

    public function locationAddressId(?int $locationAddressId): OrgQuery
    {
        $this->locationAddressId = $locationAddressId;
        return $this;
    }

    public function hasMember(null|int|User $value): static
    {
        $this->joinMembers = true;
        $this->hasMemberId = $value instanceof User ? $value->id : $value;

        return $this;
    }

    public function hasAdmin(null|int|User $value): static
    {
        $this->joinMembers = true;
        $this->hasAdminId = $value instanceof User ? $value->id : $value;

        return $this;
    }

    public function hasOwner(null|int|User $value): static
    {
        $this->ownerId = $value instanceof User ? $value->id : $value;

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
            'ownerId',
            'creatorId',
            'paymentMethodId',
            'locationAddressId',
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
            $this->subQuery->andWhere([
                'orgsMembers.userId' => $this->hasMemberId,
            ]);
        }
        if ($this->hasAdminId !== null) {
            $this->subQuery->andWhere([
                'orgsMembers.userId' => $this->hasAdminId,
                'orgsMembers.admin' => true,
            ]);
        }

        return parent::beforePrepare();
    }
}
