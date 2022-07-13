<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\commerce\elements\Order;
use craft\elements\User;
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
    private bool $_requireOrgAdmin = false;
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
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionGetOrders($id): Response
    {
        /** @var Org $org */
        $org = Org::find()->id($id)->one();

        if (!$org) {
            throw new NotFoundHttpException();
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
    //
    // /**
    //  * @throws Throwable
    //  * @throws ForbiddenHttpException
    //  */
    // public function actionSaveOrg(): Response
    // {
    //     $this->_requireOrgAdmin = true;
    //     $currentUser = Craft::$app->getUser()->getIdentity();
    //     $orgValues = Craft::$app->getRequest()->getBodyParam('org', []);
    //     $orgId = $orgValues['id'] ?? null;
    //     $isNewOrg = !$orgId;
    //     $org = $orgId ? $this->_getAllowedOrgById($orgId) : new User([
    //         'isOrg' => true,
    //     ]);
    //
    //     $org->setAttributes($orgValues);
    //
    //     if (!Craft::$app->getElements()->saveElement($org)) {
    //         return $this->asModelFailure($org);
    //     }
    //
    //     if ($isNewOrg) {
    //         $org->addOrgMember($currentUser->id, true);
    //     }
    //
    //     return $this->asModelSuccess($org);
    // }
    //
    // /**
    //  * @throws ForbiddenHttpException
    //  * @throws Exception
    //  */
    // public function actionRemoveMember($orgId, $memberId): Response
    // {
    //     $this->_requireOrgAdmin = true;
    //     $org = $this->_getAllowedOrgById($orgId);
    //     $org->removeOrgMember($memberId);
    //
    //     return $this->asSuccess('Member removed.');
    // }
    //
    // /**
    //  * @throws ForbiddenHttpException
    //  * @throws Exception
    //  */
    // public function actionGetMembers($orgId): Response
    // {
    //     /** @var User|UserBehavior $org */
    //     $org = $this->_getAllowedOrgById($orgId);
    //     $members = UserQueryBehavior::find()->orgMemberOf($org->id)->collect()
    //         ->map(fn($member) => $this->_transformMember($member) + [
    //             'orgAdmin' => UserQueryBehavior::find()->id($org->id)->hasOrgAdmin($member->id)->exists(),
    //         ]);
    //
    //     return $this->asSuccess(data: $members->all());
    // }
    //
    // /**
    //  * @throws ForbiddenHttpException
    //  * @throws BadRequestHttpException
    //  * @throws Exception
    //  */
    // public function actionAddMember($orgId): Response
    // {
    //     $this->_requireOrgAdmin = true;
    //     $email = Craft::$app->getRequest()->getRequiredBodyParam('email');
    //     $asAdmin = Craft::$app->getRequest()->getBodyParam('admin', false);
    //     $org = $this->_getAllowedOrgById($orgId);
    //     $user = Craft::$app->getUsers()->ensureUserByEmail($email);
    //     $org->addOrgMember($user->id, $asAdmin);
    //
    //     Craft::$app->getMailer()
    //         ->composeFromKey(Module::MESSAGE_KEY_ORG_INVITE, [
    //             'user' => $user,
    //             'inviter' => Craft::$app->getUser()->getIdentity(),
    //             'org' => $org,
    //         ])
    //         ->setTo($email)
    //         ->send();
    //
    //     return $this->asSuccess();
    // }
    //
    // // owner or member
    // // must have one owner
    // public function actionChangeRole(): Response
    // {
    //     return $this->asSuccess();
    // }
    //
    // // make sure these can take a user(org)
    // // - profile, billing
    //
    // // opt-in plugin dev features?
    // // developer support?
    // // partner profile
    //
    // /**
    //  * Gets an org by id, ensuring the logged-in user has permission to do so.
    //  * @throws ForbiddenHttpException
    //  */
    // private function _getAllowedOrgById(int $id): User|UserBehavior
    // {
    //     $currentUser = Craft::$app->getUser()->getIdentity();
    //     $query = UserQueryBehavior::find()->id($id);
    //
    //     if ($this->_requireOrgAdmin) {
    //         $query->hasOrgAdmin($currentUser->id);
    //     } else {
    //         $query->hasOrgMember($currentUser->id);
    //     }
    //
    //     $org = $query->one();
    //     if (!$org) {
    //         throw new ForbiddenHttpException();
    //     }
    //
    //     return $org;
    // }
    //
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
