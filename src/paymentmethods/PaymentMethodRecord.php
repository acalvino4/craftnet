<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftnet\paymentmethods;

use Craft;
use craft\commerce\models\PaymentSource;
use craft\commerce\Plugin;
use craft\db\ActiveRecord;
use craft\elements\Address;
use craftnet\db\Table;
use craftnet\orgs\Org;
use craftnet\orgs\OrgQuery;

/**
 * @property int $id
 * @property int $ownerId
 * @property int $billingAddressId
 * @property int $paymentSourceId
 * @property-read \craft\elements\Address|null $billingAddress
 * @property-read null|\craft\commerce\models\PaymentSource $paymentSource
 */
class PaymentMethodRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return Table::PAYMENTMETHODS;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['paymentSourceId', 'ownerId'], 'required'],
        ];
    }

    public function getPaymentSource(): ?PaymentSource
    {
        return Plugin::getInstance()
            ->getPaymentSources()
            ->getPaymentSourceById($this->paymentSourceId);
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddressId
            ? Address::find()->id($this->billingAddressId)->one()
            : null;
    }

    public function getOrgs(): OrgQuery
    {
        return Org::find()->paymentMethodId($this->id);
    }

    public function afterDelete()
    {
        parent::afterDelete();

        Plugin::getInstance()
            ->getPaymentSources()
            ->deletePaymentSourceById($this->paymentSource->id);

        if ($this->billingAddressId) {
            Craft::$app->getElements()->deleteElementById($this->billingAddressId);
        }
    }
}
