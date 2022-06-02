<?php

namespace craftnet\controllers\id;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use craftnet\behaviors\UserBehavior;
use yii\web\Response;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    // Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Account index.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $config = Craft::$app->getConfig()->getConfigFromFile('craftid');
        $redirectUrl = $config['consoleUrl'] . Craft::$app->getRequest()->getUrl();
        return $this->redirect($redirectUrl);
    }
}
