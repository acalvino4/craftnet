<?php

namespace craftnet\oauthserver\models;

use craft\base\Model;
use DateTime;

/**
 * Class RefreshToken
 */
class RefreshToken extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null
     */
    public $id;

    /**
     * @var int|null
     */
    public $accessTokenId;

    /**
     * @var string|null
     */
    public $identifier;

    /**
     * @var DateTime|null
     */
    public $expiryDate;

    /**
     * @var DateTime|null
     */
    public $dateCreated;

    /**
     * @var DateTime|null
     */
    public $dateUpdated;

    /**
     * @var string|null
     */
    public $uid;

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'expiryDate';
        return $attributes;
    }
}
