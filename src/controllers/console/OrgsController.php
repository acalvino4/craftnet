<?php

namespace craftnet\controllers\console;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use craftnet\behaviors\UserBehavior;
use craftnet\db\Table;
use Illuminate\Support\Collection;
use yii\web\Response;

class OrgsController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requireAcceptsJson();

        return parent::beforeAction($action);
    }

    /**
     * @return Response
     */
    public function actionGet($id): Response
    {
        /** @var User|UserBehavior $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();
        // return $this->asSuccess(data: $orgs->all());
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

        $orgs = $currentUser->getOrgs()
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
