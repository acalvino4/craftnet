<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craftnet\behaviors\UserQueryBehavior;
use craftnet\enums\OrgMemberRole;
use craftnet\Module;
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
    public function actionRemoveMember(int $orgId, int $memberId): Response
    {
        $org = $this->_getOrgById($orgId);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        $user = Craft::$app->getUsers()->getUserById($memberId);

        if (!$user) {
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
        $org = $this->_getOrgById($orgId);

        /** @var UserQuery|UserQueryBehavior $userQuery */
        $userQuery = User::find();
        $members = $userQuery->ofOrg($org->id)->collect()
            ->map(fn($member) => $this->_transformMember($member) + [
                    'owner' => (clone $userQuery)->id($member->id)->orgOwner(true)->exists(),
                ]);

        return $this->asSuccess(data: $members->all());
    }

    /**
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionAddMember(int $orgId): Response
    {
        $org = $this->_getOrgById($orgId);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        $email = Craft::$app->getRequest()->getRequiredBodyParam('email');
        $role = OrgMemberRole::tryFrom(Craft::$app->getRequest()->getBodyParam('role')) ?? OrgMemberRole::Member;
        $user = Craft::$app->getUsers()->ensureUserByEmail($email);

        if ($role === OrgMemberRole::Owner) {
            $org->addOwner($user);
        } else {
            $org->addMember($user);
        }

        // TODO: split to different controller
        $success = Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_INVITE, [
                'user' => $user,
                'inviter' => Craft::$app->getUser()->getIdentity(),
                'org' => $org,
            ])
            ->setTo($email)
            ->send();

        return $success ? $this->asSuccess() : $this->asFailure();
    }

    /**
     * @throws \yii\db\Exception
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws UserException
     */
    public function actionSetRole(int $orgId, int $memberId): Response
    {
        $org = $this->_getOrgById($orgId);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        /** @var UserQuery|UserQueryBehavior $userQuery */
        $userQuery = User::find()->id($memberId);
        $user = $userQuery->ofOrg($org)->one();

        if (!$user) {
            throw new NotFoundHttpException();
        }

        try {
            $role = OrgMemberRole::from($this->request->getRequiredBodyParam('role'));
        } catch (ValueError $e) {
            return $this->asFailure('Invalid role.');
        }

        return $org->setMemberRole($user, $role) ? $this->asSuccess() : $this->asFailure();
    }
}
