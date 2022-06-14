<?php

namespace craftnet\controllers\console;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use craftnet\behaviors\UserBehavior;
use yii\web\Response;

class OrgsController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requireAcceptsJson();

        return parent::beforeAction($action);
    }

    /**
     * Get orgs for current user
     *
     * @return Response
     */
    public function actionUserOrgs(): Response
    {
        /** @var User|UserBehavior $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();

        return $this->asSuccess(data: $currentUser->getOrgs());
    }

    public function actionLeaveOrg(): Response
    {
        return $this->asSuccess();
    }

    public function actionNewOrg(): Response
    {
        return $this->asSuccess();
    }

    // Convert your account to an organization
    // You cannot convert this account to an organization until you leave all organizations that you’re a member of.
    public function actionConvertToOrg(): Response
    {
        return $this->asSuccess();

    }

    public function actionInviteMemberToOrg(): Response
    {
        return $this->asSuccess();

    }

    public function actionRemoveMember(): Response
    {
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
