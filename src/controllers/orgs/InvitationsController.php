<?php

namespace craftnet\controllers\orgs;

use Craft;
use craftnet\enums\OrgMemberRole;
use craftnet\Module;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class InvitationsController extends SiteController
{
    /**
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionSendInvitation(int $orgId): Response
    {
        $org = $this->_getOrgById($orgId);
        $email = Craft::$app->getRequest()->getRequiredBodyParam('email');
        $role = OrgMemberRole::tryFrom(Craft::$app->getRequest()->getBodyParam('role')) ?? OrgMemberRole::Member;
        $user = Craft::$app->getUsers()->ensureUserByEmail($email);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        try {
            if ($role === OrgMemberRole::Owner) {
                $org->addOwner($user, ['enabled' => false]);
            } else {
                $org->addMember($user, ['enabled' => false]);
            }
        } catch (Exception $e) {
            return $this->asFailure('Unable to add member');
        }

        $created = Module::getInstance()?->getOrgs()->createInvitation($org, $user);

        if (!$created) {
            return $this->asFailure('Unable to create invitation');
        }

        $sent = Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_INVITATION, [
                'user' => $user,
                'inviter' => $this->_currentUser,
                'org' => $org,
            ])
            ->setTo($email)
            ->send();

        return $sent ? $this->asSuccess('Invitation sent') : $this->asFailure('Unable to send invitation');
    }

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionAcceptInvitation(int $orgId): Response
    {
        $org = $this->_getOrgById($orgId);

        try {
            Module::getInstance()?->getOrgs()->deleteExpiredInvitations();
            $org->enableMember($this->_currentUser);
            Module::getInstance()?->getOrgs()->deleteInvitation($org, $this->_currentUser);
            // TODO: should we notify org owners?
        } catch(UserException $e) {
            return $this->asFailure($e->getMessage());
        } catch(Exception $e) {
            return $this->asFailure('Unable to accept invitation');
        }

        return $this->asSuccess('Invitation accepted');
    }

    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionDeclineInvitation(int $orgId): Response
    {
        $org = $this->_getOrgById($orgId);

        try {
            // Invitation will be deleted by cascade
            $org->removeMember($this->_currentUser);
            // TODO: should we notify org owners?
        } catch(UserException $e) {
            return $this->asFailure($e->getMessage());
        } catch(Exception $e) {
            return $this->asFailure('Unable to decline invitation');
        }

        return $this->asSuccess('Invitation declined');
    }

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionGetInvitations(int $orgId): Response
    {
        $org = $this->_getOrgById($orgId);

        if (!$org->canManageMembers($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        try {
            $invitations = Module::getInstance()?->getOrgs()->getInvitationsForOrg($org);
        } catch(UserException $e) {
            return $this->asFailure($e->getMessage());
        } catch(Exception $e) {
            return $this->asFailure('Unable to get invitations');
        }

        return $this->asSuccess(data: $invitations);
    }
}
