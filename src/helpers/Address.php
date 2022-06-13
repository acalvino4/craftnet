<?php

namespace craftnet\helpers;

use craft\behaviors\CustomFieldBehavior;
use craft\elements\Address as AddressElement;

abstract class Address
{
    /**
     * Takes an Address element and returns an address array in an api/v1 compatible way.
     *
     * @param AddressElement $address
     * @return array
     */
    public static function toV1Array(AddressElement $address): array
    {
        /** @var AddressElement|CustomFieldBehavior $address */
        return [
            'firstName' => $address->getGivenName(),
            'lastName' => $address->getFamilyName(),
            'fullName' => $address->fullName,
            'title' => $address->title,
            'address1' => $address->getAddressLine1(),
            'address2' => $address->getAddressLine2(),
            'city' => $address->getLocality(),
            'zipCode' => $address->getPostalCode(),
            'attention' => $address->addressAttention, // Craftnet custom field
            'phone' => $address->addressPhone, // Craftnet custom field
            'businessName' => $address->getOrganization(),
            'businessTaxId' => $address->organizationTaxId,
            'country' => $address->getCountryCode(),
            'state' => $address->getAdministrativeArea(),
            'label' => $address->title,
        ];
    }
}
