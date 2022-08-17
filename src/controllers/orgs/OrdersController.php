<?php

namespace craftnet\controllers\orgs;

use craft\commerce\elements\db\OrderQuery;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craftnet\behaviors\OrderBehavior;
use craftnet\behaviors\OrderQueryBehavior;
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

        if (!$org->canView($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        /** @var OrderQuery|OrderQueryBehavior $orders */
        $orders = Order::find();
        $orders = $orders->orgId($org->id)->collect()
            ->map(fn(Order|OrderBehavior $order) => $order->getAttributes([
                'id',
                'number',
                'dateOrdered',
            ]) + [
                'approvalRequestedBy' => static::transformUser($order->getApprovalRequestedBy()),
                'approvalRejectedBy' => static::transformUser($order->getApprovalRejectedBy()),
            ]);

        return $this->asSuccess(data: $orders->all());
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
            $requested = $order->requestApproval($this->_currentUser, $org);
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
            $requested = $order->rejectApproval($this->_currentUser, $org);
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
