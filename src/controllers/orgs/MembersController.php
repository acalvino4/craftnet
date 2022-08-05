<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craftnet\behaviors\UserQueryBehavior;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class MembersController extends SiteController
{
    /**
     * @throws ForbiddenHttpException
     * @throws Exception
     */
    public function actionRemoveMember(int $orgId, int $userId): Response
    {
        $org = SiteController::getOrgById($orgId);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user || !$org->hasMember($user)) {
            throw new NotFoundHttpException();
        }

        return $org->removeMember($user) ? $this->asSuccess() : $this->asFailure();
    }

    /**
     * @throws ForbiddenHttpException
     * @throws Exception
     */
    public function actionGetMembers($orgId): Response
    {
        $org = SiteController::getOrgById($orgId);

        /** @var UserQuery|UserQueryBehavior $userQuery */
        $userQuery = User::find();
        $members = $userQuery->ofOrg($org->id)->collect()
            ->map(fn($member) => $this->transformUser($member) + [
                'role' => $org->getMemberRole($member),
            ]);

        return $this->asSuccess(data: $members->all());
    }

    /**
     * @throws \yii\db\Exception
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws UserException
     */
    public function actionSetRole(int $orgId, int $userId): Response
    {
        $org = SiteController::getOrgById($orgId);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        /** @var UserQuery|UserQueryBehavior $userQuery */
        $userQuery = User::find()->id($userId);
        $user = $userQuery->ofOrg($org)->one();

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $role = $this->getOrgMemberRoleFromRequest(required: true);

        return $org->setMemberRole($user, $role) ? $this->asSuccess() : $this->asFailure();
    }

    /**
     * @throws \yii\db\Exception
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws UserException
     */
    public function actionGetRole(int $orgId, int $userId): Response
    {
        $org = SiteController::getOrgById($orgId);

        if (!$org->hasMember($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

    /** @var UserQuery|UserQueryBehavior $userQuery */
        $userQuery = User::find()->id($userId);
        $user = $userQuery->ofOrg($org)->one();

        if (!$user) {
            throw new NotFoundHttpException();
        }

        return $this->asSuccess(data: ['role' => $org->getMemberRole($user)]);
    }
}
