<?php

namespace craftnet\oauthserver\services;

use Craft;
use craft\helpers\Db;
use craftnet\oauthserver\models\Client;
use craftnet\oauthserver\records\Client as ClientRecord;
use yii\base\Component;

/**
 * Class Clients
 */
class Clients extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function getClients()
    {
        $records = ClientRecord::find()->all();
        $clients = [];

        if (count($records) > 0) {
            foreach ($records as $record) {
                $clients[] = new Client($record->getAttributes());
            }
        }
        return $clients;
    }

    /**
     * @param int|null $id
     *
     * @return Client|null
     */
    public function getClientById($id)
    {
        if ($id) {
            $record = ClientRecord::findOne($id);

            if ($record) {
                return new Client($record->getAttributes());
            }
        }

        return null;
    }

    /**
     * @param $identifier
     *
     * @return Client|null
     */
    public function getClientByIdentifier($identifier)
    {
        $record = ClientRecord::findOne(['identifier' => $identifier]);

        if ($record) {
            return new Client($record->getAttributes());
        }

        return null;
    }

    /**
     * @param Client $client
     * @param bool $runValidation
     *
     * @return bool
     */
    public function saveClient(Client $client, bool $runValidation = true)
    {
        if ($runValidation && !$client->validate()) {
            Craft::info('Client not saved due to validation error.', __METHOD__);

            return false;
        }

        // is new ?
        $isNewClient = !$client->id;

        // populate record
        $record = $this->_getClientRecordById($client->id);
        $record->name = $client->name;
        $record->identifier = $client->identifier;
        $record->secret = $client->secret;
        $record->redirectUri = $client->redirectUri;
        $record->redirectUriLocked = $client->redirectUriLocked;

        // save record
        if ($record->save(false)) {
            // populate id
            if ($isNewClient) {
                $client->id = $record->id;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes a $client by its ID.
     *
     * @param int $clientId
     *
     * @return bool Whether the $client was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteClientById(int $clientId): bool
    {
        $client = $this->getClientById($clientId);

        if (!$client) {
            return false;
        }

        Db::delete('{{%oauthserver_clients}}', ['id' => $clientId]);
        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * @param int|null $id
     *
     * @return ClientRecord|static
     * @throws \Exception
     */
    private function _getClientRecordById($id = null)
    {
        if ($id) {
            $record = ClientRecord::findOne($id);
            if (!$record) {
                throw new \Exception(Craft::t('app', 'No client exists with the ID “{id}”', ['id' => $id]));
            }
        } else {
            $record = new ClientRecord();
        }
        return $record;
    }
}
