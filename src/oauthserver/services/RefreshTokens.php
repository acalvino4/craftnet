<?php

namespace craftnet\oauthserver\services;

use Craft;
use craft\helpers\Db;
use craftnet\oauthserver\models\RefreshToken;
use craftnet\oauthserver\records\RefreshToken as RefreshTokenRecord;
use yii\base\Component;

/**
 * Class RefreshTokens
 */
class RefreshTokens extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function getRefreshTokens()
    {
        $records = RefreshTokenRecord::find()->all();
        $refreshTokens = [];

        if (count($records) > 0) {
            foreach ($records as $record) {
                $refreshTokens[] = new RefreshToken($record->getAttributes());
            }
        }
        return $refreshTokens;
    }

    /**
     * @param $id
     *
     * @return RefreshToken|null
     */
    public function getRefreshTokenById($id)
    {
        if ($id) {
            $record = RefreshTokenRecord::findOne($id);

            if ($record) {
                return new RefreshToken($record->getAttributes());
            }
        }

        return null;
    }

    /**
     * @param $identifier
     *
     * @return RefreshToken|null
     */
    public function getRefreshTokenByIdentifier($identifier)
    {
        $record = RefreshTokenRecord::findOne(['identifier' => $identifier]);

        if ($record) {
            return new RefreshToken($record->getAttributes());
        }

        return null;
    }

    /**
     * @param $id
     *
     * @return RefreshToken|null
     */
    public function getRefreshTokenByAccessTokenId($id)
    {
        $record = RefreshTokenRecord::findOne(['accessTokenId' => $id]);

        if ($record) {
            return new RefreshToken($record->getAttributes());
        }

        return null;
    }

    /**
     * @param int $refreshTokenId
     *
     * @return bool
     */
    public function deleteRefreshTokenById(int $refreshTokenId): bool
    {
        $refreshToken = $this->getRefreshTokenById($refreshTokenId);

        if (!$refreshToken) {
            return false;
        }

        Db::delete('{{%oauthserver_refresh_tokens}}', ['id' => $refreshTokenId]);
        return true;
    }

    /**
     * @return bool
     */
    public function clearRefreshTokens()
    {
        Db::delete('{{%oauthserver_refresh_tokens}}');
        return true;
    }

    /**
     * @param RefreshToken $model
     *
     * @return bool
     */
    public function saveRefreshToken(RefreshToken &$model)
    {
        // is new ?
        $isNewRefreshToken = !$model->id;

        // populate record
        $record = $this->_getRefreshTokenRecordById($model->id);
        $record->accessTokenId = $model->accessTokenId;
        $record->identifier = $model->identifier;
        $record->expiryDate = $model->expiryDate;

        // save record
        if ($record->save(false)) {
            // populate id
            if ($isNewRefreshToken) {
                $model->id = $record->id;
            }
            return true;
        } else {
            return false;
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @param null $id
     *
     * @return RefreshTokenRecord|static
     * @throws \Exception
     */
    private function _getRefreshTokenRecordById($id = null)
    {
        if ($id) {
            $record = RefreshTokenRecord::findOne($id);
            if (!$record) {
                throw new \Exception(Craft::t('app', 'No refresh token exists with the ID “{id}”', ['id' => $id]));
            }
        } else {
            $record = new RefreshTokenRecord();
        }

        return $record;
    }
}
