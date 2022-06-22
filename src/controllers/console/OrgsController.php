<?php

namespace craftnet\controllers\console;

use Craft;
use craft\commerce\elements\db\OrderQuery;
use craft\commerce\elements\Order;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\web\Controller;
use craftnet\behaviors\OrderQueryBehavior;
use craftnet\behaviors\UserBehavior;
use craftnet\behaviors\UserQueryBehavior;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class OrgsController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requireAcceptsJson();

        return parent::beforeAction($action);
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function actionGet($id): Response
    {
        $org = $this->_getAllowedOrgById($id);
        return $this->asSuccess(data: $org->getAttributes([
            'id',
            'displayName',
        ]) + [
            'photo' => $org->photo->getAttributes(['id', 'url']),
        ]);
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function actionGetOrders($id): Response
    {
        $org = $this->_getAllowedOrgById($id);

        /** @var OrderQuery|OrderQueryBehavior $query */
        $query = Order::find();
        $orders = $query->orgId($org->id)->collect()
            ->map(fn(Order $order) => $order->getAttributes([
                'id',
                'number',
                'dateOrdered',
            ]));

        return $this->asSuccess(data: $orders->all());
    }

    /**
     * Get all orgs current user is a member of
     *
     * @return Response
     */
    public function actionGetAll(): Response
    {
        /** @var User|UserBehavior $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();

        $orgs = $currentUser->findOrgs()->collect()
            ->map(fn($org) => $org->getAttributes([
                'id',
                'displayName',
            ]) + [
                'photo' => $org->photo->getAttributes(['id', 'url']),
            ]
        );

        return $this->asSuccess(data: $orgs->all());
    }

    public function actionNewOrg(): Response
    {
        // orgs/create
        // create an org user
        // assign current user as admin
        return $this->asSuccess();
    }

    public function actionLeaveOrg(): Response
    {
        // can't leave if you are the sole admin
        return $this->asSuccess();
    }

    // Convert your account to an organization
    // You cannot convert this account to an organization until you leave all organizations that you’re a member of.
    // public function actionConvertToOrg(): Response
    // {
    //     return $this->asSuccess();
    // }

    public function actionInviteMemberToOrg(): Response
    {
        // require admin
        return $this->asSuccess();
    }

    public function actionRemoveMember(): Response
    {
        // require admin
        return $this->asSuccess();
    }

    // owner or member
    // must have one owner
    public function actionChangeRole(): Response
    {
        return $this->asSuccess();
    }

    // make sure these can take a user(org)
    // - profile, billing

    // opt-in plugin dev features?
    // developer support?
    // partner profile

    /**
     * Gets an org by id, ensuring the logged-in user has permission to do so.
     * @throws ForbiddenHttpException
     */
    private function _getAllowedOrgById(int $id): User
    {
        /** @var User|UserBehavior $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();

        /** @var UserQuery|UserQueryBehavior $query */
        $query = $currentUser->findOrgs()->id($id);
        $org = $query->one();

        if (!$org) {
            throw new ForbiddenHttpException();
        }

        return $org;
    }
}
