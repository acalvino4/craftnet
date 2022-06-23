<?php

namespace craftnet\controllers\console;

use Craft;
use craft\commerce\elements\db\OrderQuery;
use craft\commerce\elements\Order;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\web\Controller;
use craftnet\behaviors\OrderQueryBehavior;
use craftnet\behaviors\UserBehavior;
use craftnet\behaviors\UserQueryBehavior;
use Throwable;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class OrgsController extends Controller
{
    private bool $_requireOrgAdmin = false;

    public function beforeAction($action): bool
    {
        $this->requireAcceptsJson();

        return parent::beforeAction($action);
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
        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
        $asAdmin = Craft::$app->getRequest()->getBodyParam('admin', false);
        $org = $this->_getAllowedOrgById($orgId);
        $org->addOrgMember($userId, $asAdmin);

        // require admin
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
        /** @var User|UserBehavior $currentUser */
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
