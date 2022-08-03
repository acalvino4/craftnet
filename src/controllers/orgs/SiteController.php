<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use craftnet\controllers\console\BaseController;
use craftnet\orgs\Org;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class SiteController extends BaseController
{
    protected ?User $_currentUser = null;

    public function beforeAction($action): bool
    {
        $this->requireAcceptsJson();
        $this->_currentUser = Craft::$app->getUser()->getIdentity();
        return parent::beforeAction($action);
    }
}
