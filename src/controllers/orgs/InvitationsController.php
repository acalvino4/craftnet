<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\elements\User;
use craftnet\behaviors\UserBehavior;
use craftnet\Module;
use craftnet\orgs\InvitationRecord;
use craftnet\orgs\MemberRoleEnum;
use craftnet\orgs\Org;
use Illuminate\Support\Collection;
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

        if (!$org->canManageMembers($this->currentUser)) {
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
                'sender' => $this->currentUser,
                'org' => $org,
            ])
            ->setTo($email)
            ->send();

        return $sent ? $this->asSuccess('Invitation sent.') : $this->asFailure();
    }

    public function actionAcceptInvitation(int $orgId): Response
    {
        $org = SiteController::getOrgById($orgId);
        $invitation = $org->getInvitationForUser($this->currentUser);

        if (!$invitation) {
            throw new NotFoundHttpException('Invitation not found.');
        }

        if ($org->hasMember($this->currentUser)) {
            return $this->asFailure('User is already a member of this organization.');
        }

        if ($invitation->admin) {
            $org->addAdmin($this->currentUser);
        } else {
            $org->addMember($this->currentUser);
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
        $invitation = $org->getInvitationForUser($this->currentUser);

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

        if (!$org->canManageMembers($this->currentUser)) {
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
    public function actionGetInvitationsForOrg(int $orgId): Response
    {
        $org = SiteController::getOrgById($orgId);

        if (!$org->canManageMembers($this->currentUser)) {
            throw new ForbiddenHttpException();
        }

        $invitations = Collection::make($org->getInvitations())
            ->map(fn(InvitationRecord $invitation) => static::transformInvitation($invitation));

        return $this->asSuccess(data: ['invitations' => $invitations]);
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function actionGetInvitationsForUser(int $userId): Response
    {
        if ($userId !== $this->currentUser->id) {
            throw new ForbiddenHttpException();
        }

        /** @var User|UserBehavior $user */
        $user = User::find()->id($userId)->one();
        $invitations = Collection::make($user->getOrgInvitations())
            ->map(fn(InvitationRecord $invitation) => static::transformInvitation($invitation) + [
                'org' => static::transformOrg(Org::find()->id($invitation->orgId)->one())
            ]);

        return $this->asSuccess(data: ['invitations' => $invitations]);
    }

    private static function transformInvitation(InvitationRecord $invitation): array
    {
        $user = Craft::$app->getUsers()->getUserById($invitation->userId);

        return [
            'user' => static::transformUser($user),
            'role' => $invitation->admin ? MemberRoleEnum::Admin() : MemberRoleEnum::Member(),
            'dateCreated' => $invitation->dateCreated,
        ];
    }
}
