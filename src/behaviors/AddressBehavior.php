<?php

namespace craftnet\behaviors;

use craft\commerce\behaviors\CustomerAddressBehavior;
use craft\elements\Address;
use craftnet\orgs\Org;
use craftnet\orgs\OrgQuery;
use yii\base\Behavior;

/**
 * @property-read OrgQuery $orgs
 * @property-read Address $owner
 * @mixin CustomerAddressBehavior
 */
class AddressBehavior extends Behavior
{
    public function getOrgs(): OrgQuery
    {
        return Org::find()->billingAddressId($this->owner->id);
    }
}
