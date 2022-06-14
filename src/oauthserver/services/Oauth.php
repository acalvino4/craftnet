<?php

namespace craftnet\oauthserver\services;

use Craft;
use craft\elements\User;
use craftnet\oauthserver\Module;
use yii\base\Component;

/**
 * Class Oauth
 */
class Oauth extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * @param $jwt
     *
     * @return \Lcobucci\JWT\Token
     */
    public function parseJwt($jwt)
    {
        return (new \Lcobucci\JWT\Parser())->parse($jwt);
    }

    /**
     * @return User|null
     */
    public function getAuthenticatedUser()
    {
        $headers = Craft::$app->getRequest()->getHeaders();
        $jwt = substr($headers['Authorization'], 7);

        if ($jwt) {
            $token = $this->parseJwt($jwt);
            $claims = $token->getClaims();

            $accessToken = Module::getInstance()->getAccessTokens()->getAccessTokenByIdentifier($claims['jti']);

            if ($accessToken) {
                if (!empty($accessToken->userId)) {
                    return Craft::$app->users->getUserById($accessToken->userId);
                }
            }
        }

        return null;
    }

    /**
     * @return array|mixed
     */
    public function getAuthCodes()
    {
        $authCodes = Craft::$app->cache->get('oauthServer.authCodes');

        if (!$authCodes) {
            $authCodes = [];
        }

        return $authCodes;
    }

    /**
     * @return array
     */
    public function getExpiries()
    {
        return [
            'accessTokenExpiry' => Module::getInstance()->getSettings()->accessTokenExpiry,
            'refreshTokenExpiry' => Module::getInstance()->getSettings()->refreshTokenExpiry,
            'authCodeExpiry' => Module::getInstance()->getSettings()->authCodeExpiry,
        ];
    }

    /**
     * @return mixed
     */
    public function getScopes()
    {
        return Module::getInstance()->getSettings()->scopes;
    }

    /**
     * @return mixed
     */
    public function getClients()
    {
        return Module::getInstance()->getSettings()->clients;
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getClientById($id)
    {
        foreach ($this->getClients() as $client) {
            if ($client['clientId'] == $id) {
                return $client;
            }
        }
    }

    /**
     * @param bool $enabledOnly
     *
     * @return array
     */
    public function getGrants($enabledOnly = true)
    {
        $allGrants = $this->getAllGrants();
        $enabledGrants = Module::getInstance()->getSettings()->enabledGrants;
        $grants = [];

        foreach ($allGrants as $grantHandle => $grantDescription) {

            $enabled = false;

            if (in_array($grantHandle, $enabledGrants)) {
                $enabled = true;
            }

            if (($enabledOnly === true && $enabled === true) || $enabledOnly === false) {

                $expiry = null;

                if (isset($enabledGrants[$grantHandle])) {
                    $expiry = $enabledGrants[$grantHandle];
                }

                array_push($grants, [
                    'enabled' => $enabled,
                    'handle' => $grantHandle,
                    'description' => $grantDescription,
                    // 'expiry' => $expiry,
                    'expiry' => null,
                ]);
            }
        }

        return $grants;
    }

    /**
     * @param $grantClass
     * @param bool $enabledOnly
     *
     * @return mixed
     */
    public function getGrant($grantClass, $enabledOnly = true)
    {
        foreach ($this->getGrants($enabledOnly) as $grant) {
            if ($grant['handle'] === $grantClass) {
                return $grant;
            }
        }
    }

    /**
     * @param $responseType
     *
     * @return mixed
     */
    public function getGrantByResponseType($responseType)
    {
        $responseTypes = [
            'client_credentials' => 'ClientCredentialsGrant',
            'token' => 'ImplicitGrant',
            'code' => 'AuthCodeGrant',
        ];

        if (isset($responseTypes[$responseType])) {
            return $this->getGrant($responseTypes[$responseType]);
        }
    }

    /**
     * @param $grantType
     *
     * @return mixed
     */
    public function getGrantByGrantType($grantType)
    {
        $grantTypes = [
            'client_credentials' => 'ClientCredentialsGrant',
            'password' => 'PasswordGrant',
            'refresh_token' => 'RefreshTokenGrant',
            'authorization_code' => 'AuthCodeGrant',
        ];

        if (isset($grantTypes[$grantType])) {
            return $this->getGrant($grantTypes[$grantType]);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @return mixed
     */
    private function getAllGrants()
    {
        return Module::getInstance()->getSettings()->grants;
    }
}
