<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\base\Element;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craftnet\controllers\Module;
use craftnet\controllers\Order;
use craftnet\controllers\OrderQueryBehavior;
use craftnet\controllers\Throwable;
use craftnet\controllers\User;
use craftnet\controllers\UserBehavior;
use craftnet\controllers\UserQueryBehavior;
use craftnet\orgs\Org;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class CpController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requireCpRequest();

        return parent::beforeAction($action);
    }

    public function actionCreate(): ?Response
    {
        $user = Craft::$app->getUser()->getIdentity();

        // Create & populate the draft
        $org = Craft::createObject(Org::class);

        // Title & slug
        $org->title = $this->request->getQueryParam('title');
        $org->slug = $this->request->getQueryParam('slug');
        if ($org->title && !$org->slug) {
            $org->slug = ElementHelper::generateSlug($org->title, null);
        }
        if (!$org->slug) {
            $org->slug = ElementHelper::tempSlug();
        }

        // Pause time so postDate will definitely be equal to dateCreated, if not explicitly defined
        DateTimeHelper::pause();

        // Save it
        $org->setScenario(Element::SCENARIO_ESSENTIALS);
        $success = Craft::$app->getDrafts()->saveElementAsDraft($org, $user->getId(), null, null, false);

        // Resume time
        DateTimeHelper::resume();

        if (!$success) {
            return $this->asModelFailure(
                $org,
                Craft::t('app', 'Couldn’t create organization.'),
                'org',
            );
        }

        $editUrl = $org->getCpEditUrl();

        $response = $this->asModelSuccess(
            $org,
            Craft::t('app', 'Organization created.'),
            'entry',
            array_filter([
                'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
            ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function actionGet($id): Response
    {
        $org = $this->_getAllowedOrgById($id);
        return $this->asSuccess(data: static::_transformOrg($org));
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function actionGetOrders($id): Response
    {
        $org = $this->_getAllowedOrgById($id);
        $orders = OrderQueryBehavior::find()->orgId($org->id)->collect()
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
        $currentUser = Craft::$app->getUser()->getIdentity();
        $orgs = UserQueryBehavior::find()->hasOrgMember($currentUser->id)->collect()
            ->map(fn($org) => static::_transformOrg($org));

        return $this->asSuccess(data: $orgs->all());
    }

    /**
     * @throws Throwable
     * @throws ForbiddenHttpException
     */
    public function actionSaveOrg(): Response
    {
        $this->_requireOrgAdmin = true;
        $currentUser = Craft::$app->getUser()->getIdentity();
        $orgValues = Craft::$app->getRequest()->getBodyParam('org', []);
        $orgId = $orgValues['id'] ?? null;
        $isNewOrg = !$orgId;
        $org = $orgId ? $this->_getAllowedOrgById($orgId) : new User([
            'isOrg' => true,
        ]);

        $org->setAttributes($orgValues);

        if (!Craft::$app->getElements()->saveElement($org)) {
            return $this->asModelFailure($org);
        }

        if ($isNewOrg) {
            $org->addOrgMember($currentUser->id, true);
        }

        return $this->asModelSuccess($org);
    }

    /**
     * @throws ForbiddenHttpException
     * @throws Exception
     */
    public function actionRemoveMember($orgId, $memberId): Response
    {
        $this->_requireOrgAdmin = true;
        $org = $this->_getAllowedOrgById($orgId);
        $org->removeOrgMember($memberId);

        return $this->asSuccess('Member removed.');
    }

    /**
     * @throws ForbiddenHttpException
     * @throws Exception
     */
    public function actionGetMembers($orgId): Response
    {
        /** @var User|UserBehavior $org */
        $org = $this->_getAllowedOrgById($orgId);
        $members = UserQueryBehavior::find()->orgMemberOf($org->id)->collect()
            ->map(fn($member) => $this->_transformMember($member) + [
                    'orgAdmin' => UserQueryBehavior::find()->id($org->id)->hasOrgAdmin($member->id)->exists(),
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
        $this->_requireOrgAdmin = true;
        $email = Craft::$app->getRequest()->getRequiredBodyParam('email');
        $asAdmin = Craft::$app->getRequest()->getBodyParam('admin', false);
        $org = $this->_getAllowedOrgById($orgId);
        $user = Craft::$app->getUsers()->ensureUserByEmail($email);
        $org->addOrgMember($user->id, $asAdmin);

        Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_INVITE, [
                'user' => $user,
                'inviter' => Craft::$app->getUser()->getIdentity(),
                'org' => $org,
            ])
            ->setTo($email)
            ->send();

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

    /**
     * Gets an org by id, ensuring the logged-in user has permission to do so.
     * @throws ForbiddenHttpException
     */
    private function _getAllowedOrgById(int $id): User|UserBehavior
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $query = UserQueryBehavior::find()->id($id);

        if ($this->_requireOrgAdmin) {
            $query->hasOrgAdmin($currentUser->id);
        } else {
            $query->hasOrgMember($currentUser->id);
        }

        $org = $query->one();
        if (!$org) {
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

    private static function _transformOrg(User $user): array
    {
        return static::_transformMember($user) + $user->getAttributes([
                'displayName',
            ]);
    }
}
