<?php

namespace craftnet\controllers\console;

use AdamPaterson\OAuth2\Client\Provider\Stripe as StripeOauthProvider;
use Craft;
use craft\commerce\behaviors\CustomerBehavior;
use craft\commerce\models\PaymentSource;
use craft\commerce\Plugin as Commerce;
use craft\commerce\stripe\gateways\PaymentIntents;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craftnet\behaviors\PaymentSourceBehavior;
use craftnet\behaviors\UserBehavior;
use Illuminate\Support\Collection;
use League\OAuth2\Client\Token\AccessToken;
use Stripe\Account;
use Stripe\Stripe;
use Throwable;
use yii\web\NotFoundHttpException;
use yii\web\Response;


/**
 * Class StripeController
 */
class StripeController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * OAuth connect to Stripe.
     *
     * @return Response
     */
    public function actionConnect(): Response
    {
        $provider = $this->_getStripeProvider();
        $options = [
            'scope' => 'read_write',
        ];

        Craft::$app->getSession()->set('stripe.referrer', $this->request->getReferrer());
        $authorizationUrl = $provider->getAuthorizationUrl($options);

        return $this->redirect($authorizationUrl);
    }

    /**
     * OAuth callback.
     *
     * @return Response
     */
    public function actionCallback(): Response
    {
        /** @var User|UserBehavior $user */
        $user = $this->getCurrentUser();
        $provider = $this->_getStripeProvider();
        $code = $this->request->getParam('code');

        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        $resourceOwner = $provider->getResourceOwner($accessToken);

        $user->stripeAccessToken = $accessToken->getToken();
        $user->stripeAccount = $resourceOwner->getId();

        // TODO: do we still need this?
        $user->saveDeveloperInfo();

        $referrer = Craft::$app->getSession()->get('stripe.referrer');

        return $this->redirect($referrer);
    }

    /**
     * OAuth disconnect from Stripe.
     *
     * @return Response
     */
    public function actionDisconnect(): Response
    {
        /** @var User|UserBehavior $user */
        $user = $this->getCurrentUser();

        $provider = $this->_getStripeProvider();
        $accessToken = new AccessToken(['access_token' => $user->stripeAccessToken]);
        $resourceOwner = $provider->getResourceOwner($accessToken);
        $accountId = $resourceOwner->getId();

        $craftIdConfig = Craft::$app->getConfig()->getConfigFromFile('craftid');

        Stripe::setClientId($craftIdConfig['stripeClientId']);
        Stripe::setApiKey($craftIdConfig['stripeApiKey']);

        $account = Account::retrieve($accountId);
        $account->deauthorize();

        $user->stripeAccessToken = null;
        $user->stripeAccount = null;
        $user->saveDeveloperInfo();

        return $this->asJson(['success' => true]);
    }

    /**
     * Returns Stripe account for the current user.
     *
     * @return Response
     */
    public function actionAccount(): Response
    {
        /** @var User|UserBehavior $user */
        $user = $this->getCurrentUser();

        if ($user->stripeAccessToken) {
            Stripe::setApiKey($user->stripeAccessToken);
            $account = Account::retrieve();
            return $this->asJson($account);
        }

        return $this->asJson(null);
    }

    // Private Methods
    // =========================================================================

    /**
     * @return StripeOauthProvider
     */
    private function _getStripeProvider(): StripeOauthProvider
    {
        $craftIdConfig = Craft::$app->getConfig()->getConfigFromFile('craftid');

        $provider = new StripeOauthProvider([
            'clientId' => $craftIdConfig['stripeClientId'],
            'clientSecret' => $craftIdConfig['stripeApiKey'],
            'redirectUri' => UrlHelper::actionUrl('craftnet/console/stripe/callback'),
        ]);

        return $provider;
    }
}
