<?php

namespace craftnet\controllers\orgs;

use Craft;
use craftnet\Module;
use yii\base\UserException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class InvitationsController extends SiteController
{
    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\db\Exception
     * @throws \yii\base\Exception
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\base\UserException
     */
    public function actionSendInvitation(int $orgId): Response
    {
        $org = SiteController::getOrgById($orgId);
        $email = Craft::$app->getRequest()->getRequiredBodyParam('email');
        $owner = (bool) Craft::$app->getRequest()->getBodyParam('owner');
        $user = Craft::$app->getUsers()->ensureUserByEmail($email);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        try {
            $created = $org->createInvitation($user, $owner);
        } catch (UserException $e) {
            return $this->asFailure($e->getMessage());
        }

        if (!$created) {
            return $this->asFailure();
        }

        $sent = Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_INVITATION, [
                'user' => $user,
                'inviter' => $this->_currentUser,
                'org' => $org,
            ])
            ->setTo($email)
            ->send();

        return $sent ? $this->asSuccess() : $this->asFailure();
    }

    /**
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\base\UserException
     */
    public function actionAcceptInvitation(int $orgId): Response
    {
        $org = SiteController::getOrgById($orgId);
        $invitation = $org->getInvitation($this->_currentUser);

        if (!$invitation) {
            throw new NotFoundHttpException();
        }

        if ($invitation->owner) {
            $org->addOwner($this->_currentUser);
        } else {
            $org->addMember($this->_currentUser);
        }

        $org->deleteInvitation($this->_currentUser);

        return $this->asSuccess();
    }

    /**
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDeclineInvitation(int $orgId): Response
    {
        $org = SiteController::getOrgById($orgId);
        $invitation = $org->getInvitation($this->_currentUser);

        if (!$invitation) {
            throw new NotFoundHttpException();
        }

        $deleted = $org->deleteInvitation($this->_currentUser);

        return $deleted ? $this->asSuccess() : $this->asFailure();
    }

    /**
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionCancelInvitation(int $orgId, int $userId): Response
    {
        $org = SiteController::getOrgById($orgId);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $deleted = $org->deleteInvitation($user);

        return $deleted ? $this->asSuccess() : $this->asFailure();
    }

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionGetInvitations(int $orgId): Response
    {
        $org = SiteController::getOrgById($orgId);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        $invitations = $org->getInvitations();

        return $this->asSuccess(data: $invitations);
    }
}
