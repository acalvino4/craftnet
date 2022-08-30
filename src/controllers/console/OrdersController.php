<?php

namespace craftnet\controllers\console;

use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craftnet\behaviors\OrderBehavior;
use craftnet\controllers\orgs\SiteController;
use craftnet\orgs\Org;
use Illuminate\Support\Collection;
use yii\base\UserException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class OrdersController extends SiteController
{
    public function actionGetOrder(string $orderNumber): ?Response
    {
        $order = Order::find()
            ->number($orderNumber)
            ->one();

        if (!$order || !$order->canViewOrder($this->currentUser)) {
            throw new ForbiddenHttpException();
        }

        return $this->asSuccess(data: ['order' => self::transformOrderDetail($order)]);
    }

    public function actionGetOrders(?int $orgId = null): ?Response
    {
        $org = $orgId ? Org::find()->id($orgId)->one() : null;

        if ($org && !$org->canViewOrders($this->currentUser)) {
            throw new ForbiddenHttpException();
        }

        $approvalPending = $this->request->getParam('approvalPending', false);
        $orders = Order::find();

        if ($org) {
            $orders->orgId($org->id);
        } else {
            $orders->customer($this->currentUser)->withOrgOrders(false);
        }

        if ($approvalPending) {
            $orders->approvalPending(true);
        } else {
            $orders->isCompleted(true);
        }

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
            ->map(fn(Order|OrderBehavior $order) => self::transformOrder($order));

        return $this->asSuccess(data: $this->formatPagination($orders, $total, $page, $limit));
    }

    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionRequestApproval(string $orderNumber): ?Response
    {
        $org = $this->getAllowedOrgFromRequest();

        $order = Order::find()
            ->number($orderNumber)
            ->one();

        if (!$order) {
            throw new ForbiddenHttpException();
        }

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
    public function actionRejectRequest(string $orderNumber): ?Response
    {
        $org = $this->getAllowedOrgFromRequest();
        $order = Order::find()
            ->number($orderNumber)
            ->one();

        if (!$order) {
            throw new ForbiddenHttpException();
        }

        try {
            $requested = $order->rejectApproval($this->currentUser, $org);
        } catch(UserException $e) {
            return $this->asFailure($e->getMessage());
        }

        return $requested ? $this->asSuccess('Order approval rejected.') : $this->asFailure();
    }
}
