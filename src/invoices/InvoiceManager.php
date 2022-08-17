<?php

namespace craftnet\invoices;

use craft\commerce\elements\db\OrderQuery;
use craft\commerce\elements\Order;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craftnet\behaviors\OrderQueryBehavior;
use craftnet\Module;
use craftnet\orgs\Org;
use yii\base\Component;

class InvoiceManager extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Get invoices.
     *
     * @param User|Org $owner
     * @param string|null $searchQuery
     * @param int $limit
     * @param $page
     * @param $orderBy
     * @param $ascending
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function getInvoices(User|Org $owner, string $searchQuery = null, int $limit, $page, $orderBy, $ascending): array
    {
        $query = $this->_createInvoiceQuery($owner, $searchQuery);

        $perPage = $limit;
        $offset = ($page - 1) * $perPage;

        if ($orderBy) {
            $query->orderBy([$orderBy => $ascending ? SORT_ASC : SORT_DESC]);
        }

        $query
            ->offset($offset)
            ->limit($limit);

        $results = $query->all();

        return $this->transformInvoices($owner, $results);
    }

    /**
     * Get total invoices.
     *
     * @param User|org $owner
     * @param string|null $searchQuery
     * @return int
     */
    public function getTotalInvoices(User|org $owner, string $searchQuery = null): int
    {
        $invoiceQuery = $this->_createInvoiceQuery($owner, $searchQuery);

        return $invoiceQuery->count();
    }

    /**
     * Get invoice by its number.
     *
     * @param User|Org $owner
     * @param $number
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function getInvoiceByNumber(User|Org $owner, $number)
    {
        $query = $this->_createInvoiceQuery($owner);
        $query->andWhere(Db::parseParam('commerce_orders.number', $number));

        $result = $query->one();

        return $this->transformInvoice($owner, $result);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param User|Org $owner
     * @param $results
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    private function transformInvoices(User|Org $owner, $results)
    {
        $orders = [];

        foreach ($results as $result) {
            $order = $this->transformInvoice($owner, $result);
            $orders[] = $order;
        }

        return $orders;
    }

    /**
     * @param User|Org $owner
     * @param $result
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    private function transformInvoice(User|Org $owner, $result)
    {
        $order = $result->getAttributes(['number', 'datePaid', 'shortNumber', 'itemTotal', 'totalPrice', 'billingAddress', 'pdfUrl']);
        $order['pdfUrl'] = UrlHelper::actionUrl("commerce/downloads/pdf?number={$result->number}");

        // Line Items
        $lineItems = [];

        foreach ($result->lineItems as $lineItem) {
            $lineItems[] = $lineItem->getAttributes([
                'description',
                'salePrice',
                'qty',
                'subtotal',
            ]);
        }

        $order['lineItems'] = $lineItems;

        // Transactions
        $transactionResults = $result->getTransactions();

        $transactions = [];

        foreach ($transactionResults as $transactionResult) {
            $transactionGateway = $transactionResult->getGateway();

            $transactions[] = [
                'type' => $transactionResult->type,
                'status' => $transactionResult->status,
                'amount' => $transactionResult->amount,
                'paymentAmount' => $transactionResult->paymentAmount,
                'gatewayName' => ($transactionGateway ? $transactionGateway->name : null),
                'dateCreated' => $transactionResult->dateCreated,
            ];
        }

        $order['transactions'] = $transactions;

        // CMS licenses
        $order['cmsLicenses'] = Module::getInstance()->getCmsLicenseManager()->transformLicensesForOwner($result->cmsLicenses, $owner);

        // Plugin licenses
        $order['pluginLicenses'] = Module::getInstance()->getPluginLicenseManager()->transformLicensesForOwner($result->pluginLicenses, $owner);

        return $order;
    }

    /**
     * @param User|Org $owner
     * @param string|null $searchQuery
     * @return Query
     */
    private function _createInvoiceQuery(User|Org $owner, string $searchQuery = null): Query
    {
        /** @var OrderQuery|OrderQueryBehavior $query */
        $query = Order::find();
        $query->isCompleted(true);
        $query->limit(null);
        $query->orderBy('dateOrdered desc');

        if ($owner instanceof Org) {
            $query->orgId($owner->id);
        } else {
            $query->customerId($owner->id);
        }

        if ($searchQuery) {
            $query->andWhere([
                'or',
                ['ilike', 'commerce_orders.number', $searchQuery],
            ]);
        }

        return $query;
    }
}
