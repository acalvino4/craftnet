<?php

namespace craftnet\controllers\api\v1;

use Craft;
use craft\commerce\behaviors\CustomerBehavior;
use craft\commerce\Plugin as Commerce;
use craft\elements\User;
use craftnet\controllers\api\BaseApiController;
use craftnet\controllers\api\RateLimiterTrait;
use yii\helpers\Json;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

/**
 * Class AccountController
 */
class AccountController extends BaseApiController
{
    use RateLimiterTrait;

    // Public Methods
    // =========================================================================

    /**
     * Handles /v1/account requests.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        /** @var User|CustomerBehavior $user */
        if (($user = Craft::$app->getUser()->getIdentity(false)) === null) {
            throw new UnauthorizedHttpException('Not Authorized');
        }

        // Purchased plugins
        $purchasedPlugins = [];

        // TODO: @tim ask about this
        foreach ($user->purchasedPlugins->all() as $purchasedPlugin) {
            $purchasedPlugins[] = [
                'name' => $purchasedPlugin->title,
                'developerName' => $purchasedPlugin->getAuthor()->developerName,
                'developerUrl' => $purchasedPlugin->getAuthor()->developerUrl,
            ];
        }


        // Credit cards
        $card = null;
        $cardToken = null;
        $paymentSources = Commerce::getInstance()->getPaymentSources()->getAllPaymentSourcesByCustomerId($user->id);

        if (count($paymentSources) > 0) {
            $paymentSource = $paymentSources[0];
            $cardToken = $paymentSource->token;
            $response = Json::decode($paymentSource->response);

            if (isset($response['object'])) {
                switch ($response['object']) {
                    case 'card':
                        $card = $response;
                        break;

                    case 'source':
                    case 'payment_method':
                        $card = $response['card'];
                        break;
                }
            }
        }

        // Billing address
        $billingAddressArray = null;

        if ($billingAddress = $user->getPrimaryBillingAddress()) {
            // TODO: these property names will change and need to be normalized
            $billingAddressArray = $billingAddress->toArray();
            $billingAddressArray['country'] = $billingAddress->getCountryCode();
            $billingAddressArray['state'] = $billingAddress->getAdministrativeArea();
        }

        return $this->asJson([
            'id' => $user->getId(),
            'name' => $user->fullName,
            'email' => $user->email,
            'username' => $user->username,
            'purchasedPlugins' => $purchasedPlugins,
            'card' => $card,
            'cardToken' => $cardToken,
            'billingAddress' => $billingAddressArray,
        ]);
    }
}
