<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\commerce\elements\Order;
use craftnet\behaviors\OrderBehavior;
use craftnet\behaviors\OrderQueryBehavior;
use craftnet\Module;
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

        /** @var Order|OrderQueryBehavior $orders */
        $orders = Order::find();
        $orders = $orders->orgId($org->id)->collect()
            ->map(fn(Order $order) => $order->getAttributes([
                'id',
                'number',
                'dateOrdered',
                'approvalPending',
                'approvalRejected',
            ]));

        return $this->asSuccess(data: $orders->all());
    }

    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionRequestApproval(int $orgId, int $orderId): ?Response
    {
        $org = static::getOrgById($orgId);

        if (!$org->hasMember($this->_currentUser)) {
            throw new ForbiddenHttpException('User is not a member of this organization.');
        }

        $order = static::getOrderById($orderId);

        if ($order->customerId !== $this->_currentUser->id) {
            throw new ForbiddenHttpException('Order does not belong to this user');
        }

        $order->orgId = $org->id;
        $order->setApprovalPending(true);
        $saved = Craft::$app->getElements()->saveElement($order);

        if (!$saved) {
            return $this->asFailure();
        }

        $owner = $org->getOwner();
        $sent = Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_ORDER_APPROVAL_REQUEST, [
                'recipient' => $owner,
                'requester' => $this->_currentUser,
                'order' => $order,
                'org' => $org,
            ])
            ->setTo($owner->email)
            ->send();

        return $sent ? $this->asSuccess('Order approval requested.') : $this->asFailure();
    }

    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionRejectRequest(int $orgId, int $orderId): ?Response
    {
        $org = static::getOrgById($orgId);

        if (!$org->canApproveOrders($this->_currentUser)) {
            throw new ForbiddenHttpException('Only organization owners may reject approval requests.');
        }

        $order = static::getOrderById($orderId);
        $order->setApprovalRejected(true);
        $saved = Craft::$app->getElements()->saveElement($order);

        if (!$saved) {
            return $this->asFailure();
        }

        $recipient = $order->getCustomer();
        $sent = Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_ORDER_APPROVAL_REJECT, [
                'recipient' => $recipient,
                'rejector' => $this->_currentUser,
                'order' => $order,
                'org' => $org,
            ])
            ->setTo($recipient->email)
            ->send();

        return $sent ? $this->asSuccess('Approval request rejected.') : $this->asFailure();
    }

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionApproveRequest(int $orgId, int $orderId): ?Response
    {
        $org = static::getOrgById($orgId);

        if (!$org->canApproveOrders($this->_currentUser)) {
            throw new ForbiddenHttpException('Only owners may approve approval requests.');
        }

        $order = static::getOrderById($orderId);
        $requestor = $order->customer;
        $order->setApprovalPending(false);

        // TODO: Complete order

        $saved = Craft::$app->getElements()->saveElement($order);

        if (!$saved) {
            return $this->asFailure();
        }

        $sent = Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_ORDER_APPROVAL_REQUEST, [
                'recipient' => $requestor,
                'approver' => $this->_currentUser,
                'order' => $order,
                'org' => $org,
            ])
            ->setTo($requestor->email)
            ->send();

        return $sent ? $this->asSuccess('Request approved.') : $this->asFailure();
    }

    private static function getOrderById(int $orderId): Order|OrderBehavior
    {
        $order = Order::find()->id($orderId)->one();

        if (!$order) {
            throw new NotFoundHttpException('Order not found');
        }

        return $order;
    }
}
