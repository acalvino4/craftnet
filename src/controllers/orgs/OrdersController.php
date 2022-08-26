<?php

namespace craftnet\controllers\orgs;

use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craftnet\behaviors\OrderBehavior;
use Illuminate\Support\Collection;
use yii\base\UserException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class OrdersController extends SiteController
{
    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionGetOrders(int $orgId): ?Response
    {
        $org = static::getOrgById($orgId);

        if (!$org->canView($this->currentUser)) {
            throw new ForbiddenHttpException();
        }

        $orders = Order::find();
        $orders->orgId($org->id);

        Collection::make([
            'approvalRejectedById',
            'approvalRequestedById',
            'approvalRejectedDate',
            'approvalRequested',
            'approvalPending',
        ])->mapWithKeys(fn($prop) => [
            $prop => $this->request->getParam($prop)
        ])->whereNotNull()
        ->each(fn($value, $prop) => $orders?->$prop($value));

        $limit = $this->request->getParam('limit', 10);
        $page = (int)$this->request->getParam('page', 1);
        $orderBy = $this->request->getParam('orderBy', 'dateOrdered');
        $ascending = (bool)$this->request->getParam('ascending', false);
        $offset = ($page - 1) * $limit;

        $orders
            ->search($this->request->getParam('query'))
            ->orderBy($orderBy ? [$orderBy => $ascending ? SORT_ASC : SORT_DESC] : null);

        $total = $orders->count();

        $orders = $orders->limit($limit)->offset($offset)->collect()
            ->map(fn(Order|OrderBehavior $order) => $order->getAttributes([
                'id',
                'number',
                'dateOrdered',
            ]) + [
                'approvalRequestedBy' => static::transformUser($order->getApprovalRequestedBy()),
                'approvalRejectedBy' => static::transformUser($order->getApprovalRejectedBy()),
            ]);

        return $this->asSuccess(data: $this->formatPagination($orders, $total, $page, $limit));
    }

    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionRequestApproval(int $orgId, string $orderNumber): ?Response
    {
        $org = static::getOrgById($orgId);
        $order = static::getOrderByNumber($orderNumber);

        try {
            $requested = $order->requestApproval($this->currentUser, $org);
        } catch(UserException $e) {
            return $this->asFailure($e->getMessage());
        }

        if (!$requested) {
            $this->asFailure();
        }

        Commerce::getInstance()->getCarts()->forgetCart();

        return $this->asSuccess('Order approval requested.');
    }

    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionRejectRequest(int $orgId, string $orderNumber): ?Response
    {
        $org = static::getOrgById($orgId);
        $order = static::getOrderByNumber($orderNumber);

        try {
            $requested = $order->rejectApproval($this->currentUser, $org);
        } catch(UserException $e) {
            return $this->asFailure($e->getMessage());
        }

        return $requested ? $this->asSuccess('Order approval rejected.') : $this->asFailure();
    }

    private static function getOrderByNumber(string $orderNumber): Order|OrderBehavior
    {
        $order = Order::find()->number($orderNumber)->one();

        if (!$order) {
            throw new NotFoundHttpException('Order not found');
        }

        return $order;
    }
}
