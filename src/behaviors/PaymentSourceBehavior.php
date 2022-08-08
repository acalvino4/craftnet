<?php

namespace craftnet\behaviors;

use craft\commerce\models\PaymentSource;
use craft\helpers\Json;
use craftnet\orgs\Org;
use craftnet\orgs\OrgQuery;
use yii\base\Behavior;

/**
 * @property-read null|array $card
 * @property-read PaymentSource $owner
 */
class PaymentSourceBehavior extends Behavior
{
    public function getCard(): ?array
    {
        $response = Json::decode($this->owner->response);
        $object = $response['object'] ?? null;

        return match($object) {
            'card' => $response,
            'source', 'payment_method' => $response['card'],
            default => null,
        };
    }

    public function getOrgs(): OrgQuery
    {
        return Org::find()->paymentSourceId($this->owner->id);
    }
}
