<?php

namespace craftnet\oauthserver\models;

use craft\base\Model;
use DateTime;

/**
 * Class Client
 */
class Client extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null
     */
    public $id;

    /**
     * @var string|null
     */
    public $name;

    /**
     * @var string|null
     */
    public $identifier;

    /**
     * @var string|null
     */
    public $secret;

    /**
     * @var string|null
     */
    public $redirectUri;

    /**
     * @var bool|null
     */
    public $redirectUriLocked;

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['name', 'identifier'], 'required'],
        ];
    }
}
