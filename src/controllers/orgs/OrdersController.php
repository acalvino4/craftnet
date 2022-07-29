<?php

namespace craftnet\controllers\orgs;

use craft\commerce\elements\Order;
use craftnet\behaviors\OrderQueryBehavior;
use craftnet\orgs\Org;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class OrdersController extends SiteController
{
    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionGetOrders(int $orgId): Response
    {
        /** @var Org $org */
        $org = Org::find()->id($orgId)->one();

        if (!$org) {
            throw new NotFoundHttpException();
        }

        if (!$org->canView($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        /** @var Order|OrderQueryBehavior $orders */
        $orders = Order::find();
        $orders = $orders->orgId($org->id)->collect()
            ->map(fn(Order $order) => $order->getAttributes([
                'id',
                'number',
                'dateOrdered',
            ]));

        return $this->asSuccess(data: $orders->all());
    }
}
