<?php

namespace craftnet\oauthserver\models;

use Craft;
use craft\base\Model;
use craftnet\oauthserver\Module;
use DateTime;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * Class AuthCode
 */
class AuthCode extends Model
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
    public $clientId;

    /**
     * @var int|null
     */
    public $userId;

    /**
     * @var string|null
     */
    public $identifier;

    /**
     * @var DateTime|null
     */
    public $expiryDate;

    /**
     * @var ScopeEntityInterface[]|null
     */
    public $scopes;

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
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'expiryDate';
        return $attributes;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return Module::getInstance()->getClients()->getClientById($this->clientId);
    }

    /**
     * @return \craft\elements\User|null
     */
    public function getUser()
    {
        return Craft::$app->getUsers()->getUserById($this->userId);
    }
}
