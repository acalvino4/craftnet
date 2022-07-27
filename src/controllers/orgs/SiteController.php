<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\base\Element;
use craft\commerce\elements\Order;
use craft\elements\db\UserQuery;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\UnsupportedSiteException;
use craft\services\Elements;
use craft\web\Controller;
use craftnet\behaviors\OrderQueryBehavior;
use craftnet\behaviors\UserBehavior;
use craftnet\behaviors\UserQueryBehavior;
use craftnet\Module;
use craftnet\orgs\Org;
use craftnet\orgs\OrgQuery;
use Throwable;
use yii\base\Exception;
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
    public function actionRemoveMember($orgId, $memberId): Response
    {
        $org = $this->_getOrgById($orgId);
        $success = $org->removeMember($memberId);

        return $success ? $this->asSuccess('Member removed.') : $this->asFailure('Unable to remove member.');
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
    public function actionAddMember($orgId): Response
    {
        $email = Craft::$app->getRequest()->getRequiredBodyParam('email');
        $asOwner = Craft::$app->getRequest()->getBodyParam('owner', false);
        $org = $this->_getOrgById($orgId);
        $user = Craft::$app->getUsers()->ensureUserByEmail($email);

        if ($asOwner) {
            $org->addOwner($user->id);
        } else {
            $org->addMember($user->id);
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
     * @throws ForbiddenHttpException
     */
    private function _getOrgById(int $id): Org
    {
        $org = Org::find()->id($id)->one();

        if (!$org || !$org->canView($this->_currentUser)) {
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
