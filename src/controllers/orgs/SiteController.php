<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use craftnet\orgs\Org;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class SiteController extends Controller
{
    protected ?User $_currentUser = null;

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
    protected function _getOrgById(int $id): Org
    {
        $org = Org::find()->id($id)->one();

        if (!$org) {
            throw new NotFoundHttpException();
        }

        return $org;
    }

    protected static function _transformMember(User $user): array
    {
        return $user->getAttributes([
                'id',
            ]) + [
                'photo' => $user->photo?->getAttributes(['id', 'url']),
            ];
    }

    protected static function _transformOrg(Org $org): array
    {
        return $org->getAttributes([
            'id',
            'title',
            'requireOrderApproval',
        ]) + [
            'orgLogo' => $org->orgLogo->one()?->getAttributes(['id', 'url']),
        ];
    }
}
