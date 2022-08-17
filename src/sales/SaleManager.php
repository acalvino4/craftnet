<?php

namespace craftnet\sales;

use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craftnet\db\Table;
use craftnet\orgs\Org;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginEdition;
use yii\base\Component;

class SaleManager extends Component
{
    /**
     * Get sales by plugin owner.
     *
     * @param Org $owner
     * @param string|null $searchQuery
     * @param $limit
     * @param $page
     * @return array
     */
    public function getSalesByPluginOwner(Org $owner, string $searchQuery = null, $limit, $page): array
    {
        $defaultLimit = 30;
        $perPage = $limit ?? $defaultLimit;
        $offset = ($page - 1) * $perPage;

        $query = $this->_getSalesQuery($owner, $searchQuery);

        $query
            ->offset($offset)
            ->limit($limit);

        $results = $query->all();

        foreach ($results as &$row) {
            $row['netAmount'] = number_format($row['grossAmount'] * 0.8, 2);

            // Plugin
            $hasMultipleEditions = false;
            $plugin = Plugin::findOne($row['pluginId']);

            if ($plugin) {
                $editions = $plugin->getEditions();

                if ($editions) {
                    $hasMultipleEditions = count($editions) > 1;
                }
            }

            $row['plugin'] = [
                'id' => $row['pluginId'],
                'name' => $row['pluginName'],
                'hasMultipleEditions' => $hasMultipleEditions,
            ];

            // Customer

            /** @var Org|User $owner */
            $owner = Craft::$app->getElements()->getElementById($row['ownerId']);
            $row['customer'] = [
                'id' => $row['ownerId'],
                'name' => $owner?->title ?? $owner?->name,
                'email' => $owner instanceof Org ? $owner->getOwner()->email : $owner->email,
            ];

            // Edition
            $edition = PluginEdition::findOne($row['editionId']);

            $row['edition'] = [
                'name' => $edition['name'],
                'handle' => $edition['handle'],
            ];

            // Unset attributes we don’t need anymore
            unset($row['pluginId'], $row['pluginName'], $row['ownerId'], $row['ownerFirstName'], $row['ownerLastName'], $row['ownerEmail']);
        }

        // Adjustments
        $results = ArrayHelper::index($results, 'id');
        $lineItemIds = array_keys($results);

        $adjustments = (new Query())
            ->select(['lineItemId', 'name', 'amount'])
            ->from([CommerceTable::ORDERADJUSTMENTS])
            ->where(['lineItemId' => $lineItemIds])
            ->all();

        foreach ($adjustments as $adjustment) {
            $results[$adjustment['lineItemId']]['adjustments'][] = $adjustment;
        }

        $results = array_values($results);

        return $results;
    }

    /**
     * Get total sales by plugin owner.
     *
     * @param Org $owner
     * @param string|null $searchQuery
     * @return int|string
     */
    public function getTotalSalesByPluginOwner(Org $owner, string $searchQuery = null)
    {
        $query = $this->_getSalesQuery($owner, $searchQuery);

        return $query->count();
    }

    /**
     * Get sales query.
     *
     * @param Org $owner
     * @param string|null $searchQuery
     * @return Query
     */
    private function _getSalesQuery(Org $owner, string $searchQuery = null): Query
    {
        $query = (new Query())
            ->select([
                'lineitems.id AS id',
                'plugins.id AS pluginId',
                'plugins.name AS pluginName',
                'lineitems.total AS grossAmount',
                'licenseOwners.id AS ownerId',
                'lineitems.dateCreated AS saleTime',
                'orders.email AS orderEmail',
                'elements.type AS purchasableType',
                'licenses.editionId AS editionId',
            ])
            ->from(['licenses_items' => Table::PLUGINLICENSES_LINEITEMS])
            ->innerJoin(['lineitems' => CommerceTable::LINEITEMS], '[[lineitems.id]] = [[licenses_items.lineItemId]]')
            ->innerJoin(['orders' => CommerceTable::ORDERS], '[[orders.id]] = [[lineitems.orderId]]')
            ->innerJoin(['licenses' => Table::PLUGINLICENSES], '[[licenses.id]] = [[licenses_items.licenseId]]')
            ->innerJoin(['plugins' => Table::PLUGINS], '[[plugins.id]] = [[licenses.pluginId]]')
            ->leftJoin(['licenseOwners' => CraftTable::ELEMENTS], '[[licenseOwners.id]] = [[licenses.ownerId]]')
            ->leftJoin(CraftTable::ELEMENTS, '[[elements.id]] = [[lineitems.purchasableId]]')
            ->where(['plugins.developerId' => $owner->id])
            ->orderBy(['lineitems.dateCreated' => SORT_DESC]);

        if ($searchQuery) {
            $query->andWhere([
                'or',
                ['ilike', 'orders.email', $searchQuery],
                ['ilike', 'plugins.name', $searchQuery],
                ['ilike', 'plugins.handle', $searchQuery],
            ]);
        }

        return $query;
    }
}
