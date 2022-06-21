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
use yii\web\Response;

class OrgsController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requireAcceptsJson();

        return parent::beforeAction($action);
    }

    public function actionGet($id): Response
    {
        /** @var UserQuery|UserQueryBehavior $query */
        $query = User::find()->id($id);
        $org = $query->isOrg(true)->one();

        if (!$org) {
            return $this->asFailure('Organization not found.');
        }

        return $this->asSuccess(data: $org->getAttributes());
    }

    public function actionGetOrders($id): Response
    {
        /** @var OrderQuery|OrderQueryBehavior $query */
        $query = Order::find();
        $orders = $query->orgId($id)->all();

        return $this->asSuccess(data: $orders);
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
        // can't leave if you are the sole admind
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
}
