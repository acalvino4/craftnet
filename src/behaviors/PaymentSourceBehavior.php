<?php

namespace craftnet\behaviors;

use craft\commerce\models\PaymentSource;
use craft\helpers\Json;
use craftnet\orgs\Org;
use yii\base\Behavior;

/**
 * @property-read null|array $card
 * @property-read PaymentSource $owner
 */
class PaymentSourceBehavior extends Behavior
{
    private ?Org $org = null;

    public function setOrg(?Org $org): PaymentSource
    {
        $this->org = $org;

        return $this->owner;
    }

    public function getOrg(): ?Org
    {
        return $this->org;
    }

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
}
