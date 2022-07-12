<?php

namespace craftnet\orgs;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use craftnet\db\Table;
use Illuminate\Support\Collection;
use yii\db\Connection;

class OrgQuery extends ElementQuery
{
    // public ?int $hasMember = null;
    // public ?int $hasOwner = null;
    public ?string $stripeAccessToken = null;
    public ?string $stripeAccount = null;
    public ?string $apiToken = null;
    public ?int $balance = null;
    public ?int $creatorId = null;

    protected function beforePrepare(): bool
    {
        $this->joinElementTable(Table::ORGS);
        $columns = Collection::make([
            'stripeAccessToken',
            'stripeAccount',
            'apiToken',
            'balance',
            'creatorId',
        ]);
        $this->query->select($columns->map(fn($column) => sprintf('%s.%s', Table::ORGS, $column)));

        $columns->each(function($column) {
            if (isset($this->$column)) {
                $this->subQuery->andWhere(
                    Db::parseParam(sprintf('%s.%s', Table::ORGS, $column), $this->$column)
                );
            }
        });

        return parent::beforePrepare();
    }
}
