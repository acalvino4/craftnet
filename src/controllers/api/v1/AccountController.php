<?php

namespace craftnet\controllers\api\v1;

use Craft;
use craft\behaviors\CustomFieldBehavior;
use craft\commerce\behaviors\CustomerBehavior;
use craft\commerce\Plugin as Commerce;
use craft\elements\User;
use craftnet\behaviors\UserBehavior;
use craftnet\controllers\api\BaseApiController;
use craftnet\controllers\api\RateLimiterTrait;
use craftnet\helpers\Address as AddressHelper;
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
        /** @var User|CustomerBehavior|CustomFieldBehavior|null $user */
        $user = Craft::$app->getUser()->getIdentity(false);
        if ($user === null) {
            throw new UnauthorizedHttpException('Not Authorized');
        }

        // Purchased plugins
        $purchasedPlugins = [];

        // TODO: @tim ask about this
        foreach ($user->purchasedPlugins->all() as $purchasedPlugin) {
            /** @var User|UserBehavior $author */
            $author = $purchasedPlugin->getAuthor();
            $purchasedPlugins[] = [
                'name' => $purchasedPlugin->title,
                'developerName' => $author->developerName,
                'developerUrl' => $author->developerUrl,
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
            $billingAddressArray = AddressHelper::toV1Array($billingAddress);
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
