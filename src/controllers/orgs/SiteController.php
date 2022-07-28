<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\base\Element;
use craft\commerce\elements\Order;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\errors\UnsupportedSiteException;
use craft\web\Controller;
use craftnet\behaviors\OrderQueryBehavior;
use craftnet\behaviors\UserQueryBehavior;
use craftnet\enums\OrgMemberRole;
use craftnet\Module;
use craftnet\orgs\Org;
use Throwable;
use ValueError;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SiteController extends Controller
{
    private ?User $_currentUser = null;

    public function beforeAction($action): bool
    {
        $this->requireAcceptsJson();
        $this->_currentUser = Craft::$app->getUser()->getIdentity();
        return parent::beforeAction($action);
    }

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionGet($id): Response
    {
        /** @var Org $org */
        $org = Org::find()->id($id)->one();

        if (!$org) {
            throw new NotFoundHttpException();
        }

        if (!$org->canView($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        return $this->asSuccess(data: static::_transformOrg($org));
    }

    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionGetOrders($id): Response
    {
        /** @var Org $org */
        $org = Org::find()->id($id)->one();

        if (!$org) {
            throw new NotFoundHttpException();
        }

        if (!$org->canView($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        /** @var Order|OrderQueryBehavior $orders */
        $orders = Order::find();
        $orders = $orders->orgId($org->id)->collect()
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
        $orgs = Org::find()->hasMember($this->_currentUser)->collect()
            ->map(fn($org) => static::_transformOrg($org));

        return $this->asSuccess(data: $orgs->all());
    }

    /**
     * @throws Throwable
     * @throws ForbiddenHttpException
     */
    public function actionSaveOrg(): Response
    {
        $this->requirePostRequest();
        $elementId = $this->request->getBodyParam('orgId');
        $siteId = $this->request->getBodyParam('siteId');
        $isNew = !$elementId;

        if ($isNew) {
            $element = new Org();
            if ($siteId) {
                $element->siteId = $siteId;
            }
        } else {
            $element = Org::find()
                ->status(null)
                ->siteId($siteId)
                ->id($elementId)
                ->one();

            if (!$element) {
                throw new NotFoundHttpException('Organization not found');
            }
        }

        if (!$element->canSave($this->_currentUser)) {
            throw new ForbiddenHttpException('User not authorized to save this organization.');
        }

        $element->slug = $this->request->getBodyParam('slug', $element->slug);
        $element->title = $this->request->getBodyParam('title', $element->title);
        $element->setFieldValuesFromRequest('fields');

        if ($isNew) {
            $element->creatorId = $this->_currentUser->id;
        }

        if ($element->enabled && $element->getEnabledForSite()) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        // TODO: do we need mutex?

        try {
            $success = Craft::$app->getElements()->saveElement($element);
        } catch (UnsupportedSiteException $e) {
            $element->addError('siteId', $e->getMessage());
            $success = false;
        }

        if (!$success) {
            return $this->asModelFailure(
                $element,
                Craft::t('app', 'Couldn’t save organization.'),
                'org'
            );
        }

        if ($isNew) {
            $element->addOwner($this->_currentUser);
        }

        return $this->asModelSuccess(
            $element,
            Craft::t('app', 'Organization saved.'),
        );
    }

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

        try {
            if ($role === OrgMemberRole::Owner) {
                $org->addOwner($user);
            } else {
                $org->addMember($user);
            }
        } catch (UserException $e) {
            return $this->asFailure($e->getMessage());
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

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    private function _getOrgById(int $id): Org
    {
        $org = Org::find()->id($id)->one();

        if (!$org) {
            throw new NotFoundHttpException();
        }

        if (!$org->canView($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        return $org;
    }

    private static function _transformMember(User $user): array
    {
        return $user->getAttributes([
                'id',
            ]) + [
                'photo' => $user->photo?->getAttributes(['id', 'url']),
            ];

    }

    private static function _transformOrg(Org $org): array
    {
        return $org->getAttributes([
            'id',
            'title',
        ]);
    }
}
