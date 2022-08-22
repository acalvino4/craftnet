<?php

namespace craftnet\controllers\console;

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
        $stripeAccessToken = null;
        /** @var User|UserBehavior|null $user */
        $user = $this->getCurrentUser();

        if ($user) {
            $stripeAccessToken = $user->stripeAccessToken;
        }

        $craftIdConfig = Craft::$app->getConfig()->getConfigFromFile('craftid');
        $stripePublicKey = $craftIdConfig['stripePublicKey'];

        return $this->renderTemplate('index', [
            'stripeAccessToken' => $stripeAccessToken,
            'stripePublicKey' => $stripePublicKey,
        ]);
    }
}
