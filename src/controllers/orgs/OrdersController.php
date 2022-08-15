<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\commerce\elements\db\OrderQuery;
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

        /** @var OrderQuery|OrderQueryBehavior $orders */
        $orders = Order::find();
        $orders = $orders->orgId($org->id)->collect()
            ->map(fn(Order|OrderBehavior $order) => $order->getAttributes([
                'id',
                'number',
                'dateOrdered',
            ]) + [
                'approvalRequestBy' => static::transformUser($order->getApprovalRequestedBy()),
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

        if (!$org->hasMember($this->_currentUser)) {
            throw new ForbiddenHttpException('User is not a member of this organization.');
        }

        $order = static::getOrderByNumber($orderNumber);

        if ($order->isPendingApproval()) {
            throw new ForbiddenHttpException('Order already has a pending approval request.');
        }

        if (!$order->hasCustomer($this->_currentUser)) {
            throw new ForbiddenHttpException('Order does not belong to this user');
        }

        $order->setOrg($org);
        $order->setCreator($this->_currentUser);
        $order->setApprovalRequestedBy($this->_currentUser);
        $saved = Craft::$app->getElements()->saveElement($order);

        if (!$saved) {
            return $this->asFailure();
        }

        $owner = $org->getOwner();
        $sent = Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_ORDER_APPROVAL_REQUEST, [
                'recipient' => $owner,
                'sender' => $this->_currentUser,
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
    public function actionRejectRequest(int $orgId, string $orderNumber): ?Response
    {
        $org = static::getOrgById($orgId);

        if (!$org->canApproveOrders($this->_currentUser)) {
            throw new ForbiddenHttpException('Only organization owners may reject approval requests.');
        }

        $order = static::getOrderByNumber($orderNumber);

        if (!$order->getApprovalRequestedBy()) {
            throw new ForbiddenHttpException('Order has no pending approval request.');
        }

        if ($order->getApprovalRejectedBy()) {
            throw new ForbiddenHttpException('Order has already been rejected.');
        }

        $order->setApprovalRejectedBy($this->_currentUser);
        $saved = Craft::$app->getElements()->saveElement($order);

        if (!$saved) {
            return $this->asFailure();
        }

        $recipient = $order->getCustomer();
        $sent = Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_ORDER_APPROVAL_REJECT, [
                'recipient' => $recipient,
                'sender' => $this->_currentUser,
                'order' => $order,
                'org' => $org,
            ])
            ->setTo($recipient->email)
            ->send();

        return $sent ? $this->asSuccess('Approval request rejected.') : $this->asFailure();
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
