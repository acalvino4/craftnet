<?php

namespace craftnet\oauthserver\models;

use craft\base\Model;
use craft\helpers\Json;
use craftnet\oauthserver\Module as OauthServer;
use DateTime;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * Class AccessToken
 */
class AccessToken extends Model
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
     * @var DateTime
     */
    public $expiryDate;

    /**
     * @var mixed
     */
    public $userIdentifier;

    /**
     * @var ScopeEntityInterface[]|string|null
     */
    public $scopes;

    /**
     * @var bool|null
     */
    public $isRevoked;

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
    public function init(): void
    {
        parent::init();

        if (is_string($this->scopes)) {
            $this->scopes = Json::decode($this->scopes);
        }
    }

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
     * @return Client|null
     */
    public function getClient()
    {
        if ($this->clientId) {
            return OauthServer::getInstance()->getClients()->getClientById($this->clientId);
        }

        return null;
    }

    /**
     * @return RefreshToken
     */
    public function getRefreshToken()
    {
        return OauthServer::getInstance()->getRefreshTokens()->getRefreshTokenByAccessTokenId($this->id);
    }

    /**
     * @return bool
     */
    public function hasExpired()
    {
        $now = new DateTime();

        return $now->getTimestamp() >= $this->expiryDate->getTimestamp();
    }
}
