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

    /**
     * @throws Throwable
     */
    public function actionGetCards(): ?Response
    {
        /** @var User|UserBehavior $user */
        $user = $this->getCurrentUser();

        $paymentSources = Collection::make($user->getPaymentSources())
            ->map(function(PaymentSource|PaymentSourceBehavior $paymentSource) {
                $orgs = $paymentSource->getOrgs()->collect();

                return $paymentSource->getAttributes([
                        'id',
                        'token',
                    ]) + [
                        'isPrimary' => $paymentSource->isPrimary(),
                        'card' => $paymentSource->getCard(),
                        'orgs' => $orgs->isEmpty() ? null : $paymentSource->getOrgs()->collect()
                            ->map(fn($org) => static::transformOrg($org)),
                    ];
            });

        return $this->asSuccess(data: ['cards' => $paymentSources->all()]);
    }

    /**
     * Saves a new credit card and sets it as default source for the Stripe customer.
     *
     * @return Response
     * @throws \Throwable if something went wrong when adding the payment source
     */
    public function actionAddCard(): Response
    {
        $this->requirePostRequest();

        /** @var User|CustomerBehavior $user */
        $user = $this->getCurrentUser();

        /** @var PaymentIntents $gateway */
        $gateway = Commerce::getInstance()?->getGateways()->getGatewayById(App::env('STRIPE_GATEWAY_ID'));

        if (!$gateway || !$gateway->supportsPaymentSources()) {
            return $this->asFailure();
        }

        $paymentForm = $gateway->getPaymentFormModel();
        $paymentForm->setAttributes($this->request->getBodyParams(), false);
        $description = $this->request->getBodyParam('description');
        $isPrimary = (bool) $this->request->getBodyParam('isPrimary', false);

        $paymentSource = Commerce::getInstance()
            ->getPaymentSources()
            ->createPaymentSource($user->id, $gateway, $paymentForm, $description);

        if ($isPrimary) {
            $user->setPrimaryPaymentSourceId($paymentSource->id);

            if (!Craft::$app->getElements()->saveElement($user)) {
                return $this->asFailure('Couldn’t set primary payment source for user.');
            }
        }

        // TODO: test
        $card = $paymentSource->response;

        return $this->asSuccess(data: ['card' => $card]);
    }

    public function actionSaveCard(int $paymentSourceId): ?Response
    {
        $paymentSource = Commerce::getInstance()
            ->getPaymentSources()
            ->getPaymentSourceByIdAndUserId($paymentSourceId, $this->currentUser->id);

        if (!$paymentSource) {
            throw new NotFoundHttpException();
        }

        $description = $this->request->getBodyParam('description', $paymentSource->description);
        $isPrimary = (bool) $this->request->getBodyParam('isPrimary', $paymentSource->isPrimary());

        $paymentSource->description = $description;

        if ($isPrimary !== $paymentSource->isPrimary()) {
            $this->currentUser->setPrimaryPaymentSourceId($paymentSource->id);
            if (!Craft::$app->getElements()->saveElement($this->currentUser)) {
                return $this->asFailure('Couldn’t set primary payment source for user.');
            }
        }

        $saved = Commerce::getInstance()
            ->getPaymentSources()
            ->savePaymentSource($paymentSource);

        // TODO: test
        $card = $paymentSource->response;

        return $saved ? $this->asSuccess(data: ['card' => $card]) : $this->asFailure();
    }

    /**
     * Removes the default payment source.
     *
     * @return Response
     * @throws \Throwable
     */
    public function actionRemoveCard(int $paymentSourceId): Response
    {
        $user = $this->getCurrentUser();

        /** @var PaymentSource|PaymentSourceBehavior $paymentSource */
        $paymentSource = Commerce::getInstance()
            ->getPaymentSources()
            ->getPaymentSourceByIdAndUserId($paymentSourceId, $user->id);

        if (!$paymentSource) {
            throw new NotFoundHttpException('Credit card not found.');
        }

        if ($paymentSource->getOrgs()->exists()) {
            $this->requireElevatedSession();
        }

        $success = Commerce::getInstance()
            ->getPaymentSources()
            ->deletePaymentSourceById($paymentSourceId);

        return $success ?
            $this->asSuccess('Credit card removed.') :
            $this->asFailure('Could not remove credit card.');
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
