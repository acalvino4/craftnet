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
    public function actionSendInvitation(int $orgId): Response
    {
        $org = SiteController::getOrgById($orgId);
        $email = Craft::$app->getRequest()->getRequiredBodyParam('email');
        $role = $this->getOrgMemberRoleFromRequest();
        $recipient = Craft::$app->getUsers()->ensureUserByEmail($email);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        try {
            $created = $org->createInvitation($recipient, $role);
        } catch (UserException $e) {
            return $this->asFailure($e->getMessage());
        }

        if (!$created) {
            return $this->asFailure();
        }

        $sent = Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_INVITATION, [
                'recipient' => $recipient,
                'sender' => $this->_currentUser,
                'org' => $org,
            ])
            ->setTo($email)
            ->send();

        return $sent ? $this->asSuccess('Invitation sent.') : $this->asFailure();
    }

    public function actionAcceptInvitation(int $orgId): Response
    {
        $org = SiteController::getOrgById($orgId);
        $invitation = $org->getInvitationForUser($this->_currentUser);

        if (!$invitation) {
            throw new NotFoundHttpException('Invitation not found.');
        }

        if ($org->hasMember($this->_currentUser)) {
            return $this->asFailure('User is already a member of this organization.');
        }

        if ($invitation->admin) {
            $org->addAdmin($this->_currentUser);
        } else {
            $org->addMember($this->_currentUser);
        }

        $invitation->delete();

        return $this->asSuccess('Invitation accepted.');
    }

    /**
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDeclineInvitation(int $orgId): Response
    {
        $org = SiteController::getOrgById($orgId);
        $invitation = $org->getInvitationForUser($this->_currentUser);

        if (!$invitation) {
            throw new NotFoundHttpException('Invitation not found.');
        }

        return $invitation->delete() ? $this->asSuccess('Invitation declined.') : $this->asFailure();
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
            throw new NotFoundHttpException('User not found.');
        }

        $invitation = $org->getInvitationForUser($user);

        if (!$invitation) {
            throw new NotFoundHttpException('Invitation not found.');
        }

        return $invitation->delete() ? $this->asSuccess('Invitation cancelled.') : $this->asFailure();
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

        return $this->asSuccess(data: ['invitations' => $invitations]);
    }
}
