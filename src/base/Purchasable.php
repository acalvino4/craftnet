<?php

namespace craftnet\base;

use craft\commerce\base\Purchasable as CommercePurchasable;
use craft\commerce\models\LineItem;
use craftnet\validators\LineItemOptionsValidator;

/**
 * @property-read string $type
 */
abstract class Purchasable extends CommercePurchasable implements PurchasableInterface
{
    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'type';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function getLineItemRules(LineItem $lineItem): array
    {
        return [
            ['options', LineItemOptionsValidator::class, 'purchasable' => $this],
        ];
    }
}
