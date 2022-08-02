<?php

namespace craftnet\plugins;

use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\db\Query;
use craft\elements\User;
use craft\errors\InvalidPluginException;
use craft\helpers\Db;
use craftnet\db\Table;
use craftnet\errors\LicenseNotFoundException;
use craftnet\helpers\LicenseHelper;
use craftnet\helpers\OrderHelper;
use craftnet\Module;
use craftnet\orgs\Org;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class PluginLicenseManager extends Component
{
    /**
     * Normalizes a license key by trimming whitespace and removing dashes.
     *
     * @param string $key
     * @return string
     * @throws InvalidArgumentException if $key is invalid
     */
    public function normalizeKey(string $key): string
    {
        $normalized = trim(preg_replace('/[\-]+/', '', $key));
        if (strlen($normalized) !== 24) {
            throw new InvalidArgumentException('Invalid license key: ' . $key);
        }

        return $normalized;
    }

    /**
     * Returns licenses owned by a user.
     *
     * @param User $owner
     * @param string|null $searchQuery
     * @param int|null $perPage
     * @param int $page
     * @param string|null $orderBy
     * @param bool $ascending
     * @return array
     */
    public function getLicensesByOwner(User|Org $owner, string $searchQuery = null, int $perPage = null, int $page = 1, string $orderBy = null, bool $ascending = false): array
    {
        $licenseQuery = $this->_createLicenseQueryForOwner($owner, $searchQuery);

        if ($perPage) {
            $licenseQuery
                ->offset(($page - 1) * $perPage)
                ->limit($perPage);
        }

        if ($orderBy) {
            $licenseQuery->orderBy([$orderBy => $ascending ? SORT_ASC : SORT_DESC]);
        }

        $results = $licenseQuery->all();
        $resultsArray = [];
        foreach ($results as $result) {
            $resultsArray[] = new PluginLicense($result);
        }

        return $this->transformLicensesForOwner($resultsArray, $owner);
    }

    /**
     * Returns licenses that need to be renewed in the next 45 days.
     *
     * @param int $ownerId
     * @return PluginLicense[]
     */
    public function getRenewLicensesByOwner(int $ownerId): array
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->add(new \DateInterval('P45D'));

        $results = $this->_createLicenseQuery()
            ->andWhere([
                'and',
                [
                    'l.ownerId' => $ownerId,
                ],
                [
                    'and',
                    ['<', 'expiresOn', Db::prepareDateForDb($date)],
                ],
            ])
            ->all();

        $licenses = [];
        foreach ($results as $result) {
            $licenses[] = new PluginLicense($result);
        }

        return $licenses;
    }

    /**
     * Returns licenses purchased by an order.
     *
     * @param int $orderId
     * @return PluginLicense[]]
     */
    public function getLicensesByOrder(int $orderId): array
    {
        $results = $this->_createLicenseQuery()
            ->innerJoin(['l_li' => Table::PLUGINLICENSES_LINEITEMS], '[[l_li.licenseId]] = [[l.id]]')
            ->innerJoin(['li' => CommerceTable::LINEITEMS], '[[li.id]] = [[l_li.lineItemId]]')
            ->andWhere(['li.orderId' => $orderId])
            ->all();

        $licenses = [];
        foreach ($results as $result) {
            $licenses[] = new PluginLicense($result);
        }

        return $licenses;
    }

    /**
     * Returns licenses associated with a given Craft license ID.
     *
     * @param int $cmsLicenseId
     * @return PluginLicense[]
     */
    public function getLicensesByCmsLicense(int $cmsLicenseId): array
    {
        $results = $this->_createLicenseQuery()
            ->andWhere(['l.cmsLicenseId' => $cmsLicenseId])
            ->all();

        $licenses = [];
        foreach ($results as $result) {
            $licenses[] = new PluginLicense($result);
        }

        return $licenses;
    }

    /**
     * Returns a license by its ID.
     *
     * @param int $id
     * @return PluginLicense
     */
    public function getLicenseById(int $id): PluginLicense
    {
        $result = $this->_createLicenseQuery(false, true)
            ->andWhere(['l.id' => $id])
            ->one();

        if ($result === null) {
            throw new LicenseNotFoundException($id);
        }

        return new PluginLicense($result);
    }

    /**
     * Returns a license by its key.
     *
     * @param string $key
     * @param string|null $handle the plugin handle
     * @param bool $anyStatus whether to include licenses for disabled editions
     * @return PluginLicense
     * @throws LicenseNotFoundException if $key is missing
     * @throws InvalidPluginException
     */
    public function getLicenseByKey(string $key, string $handle = null, $anyStatus = false): PluginLicense
    {
        try {
            $key = $this->normalizeKey($key);
        } catch (InvalidArgumentException $e) {
            throw new LicenseNotFoundException($key);
        }

        $query = $this->_createLicenseQuery($anyStatus, true)
            ->andWhere(['l.key' => $key]);

        if ($handle !== null) {
            $query
                ->innerJoin(['p' => Table::PLUGINS], '[[p.id]] = [[l.pluginId]]')
                ->andWhere(['p.handle' => $handle]);
        }

        $result = $query->one();

        if ($result === null) {
            // Was the plugin handle invalid?
            if ($handle && !Plugin::find()->handle($handle)->exists()) {
                throw new InvalidPluginException($handle);
            }

            throw new LicenseNotFoundException($key);
        }

        return new PluginLicense($result);
    }

    /**
     * Returns licenses by CMS license ID.
     *
     * @param int $cmsLicenseId
     * @return PluginLicense[]
     */
    public function getLicensesByCmsLicenseId(int $cmsLicenseId): array
    {
        $results = $this->_createLicenseQuery()
            ->andWhere(['cmsLicenseId' => $cmsLicenseId])
            ->orderBy(['l.pluginHandle' => SORT_ASC])
            ->all();

        $licenses = [];
        foreach ($results as $result) {
            $licenses[] = new PluginLicense($result);
        }

        return $licenses;
    }

    /**
     * Returns licenses by their developer.
     *
     * @param int $developerId
     * @param int|null $offset
     * @param int|null $limit
     * @param int|null $total
     * @return PluginLicense[]
     */
    public function getLicensesByDeveloper(int $developerId, int $offset = null, int $limit = null, int &$total = null): array
    {
        $query = $this->_createLicenseQuery()
            ->innerJoin(['p' => Table::PLUGINS], '[[p.id]] = [[l.pluginId]]')
            ->andWhere(['p.developerId' => $developerId]);

        $total = $query->count();
        $results = $query
            ->offset($offset)
            ->limit($limit)
            ->orderBy(['l.dateCreated' => SORT_ASC])
            ->all();
        $licenses = [];
        foreach ($results as $result) {
            $licenses[] = new PluginLicense($result);
        }

        return $licenses;
    }

    /**
     * Returns licenses for a given plugin.
     *
     * @param int $pluginId
     * @param int|null $editionId
     * @param bool $includeFreeEditions
     * @return PluginLicense[]
     */
    public function getLicensesByPlugin(int $pluginId, ?int $editionId = null, bool $includeFreeEditions = false): array
    {
        $query = $this->_createLicenseQuery(false, false, $includeFreeEditions)
            ->innerJoin(['p' => Table::PLUGINS], '[[p.id]] = [[l.pluginId]]')
            ->andWhere(['l.pluginId' => $pluginId]);

        if ($editionId !== null) {
            $query->andWhere(['l.editionId' => $editionId]);
        }

        $results = $query
            ->orderBy(['l.dateCreated' => SORT_ASC])
            ->all();

        $licenses = [];

        foreach ($results as $result) {
            $licenses[] = new PluginLicense($result);
        }

        return $licenses;
    }

    /**
     * Returns any licenses that are due to expire in the next 14-30 days and haven't been reminded about that yet.
     *
     * @return PluginLicense[]
     */
    public function getRemindableLicenses(): array
    {
        $rangeStart = (new \DateTime('midnight', new \DateTimeZone('UTC')))->modify('+14 days');
        $rangeEnd = (new \DateTime('midnight', new \DateTimeZone('UTC')))->modify('+30 days');

        $results = $this->_createLicenseQuery()
            ->andWhere([
                'trial' => false,
                'expirable' => true,
                'reminded' => false,
            ])
            ->andWhere(['between', 'expiresOn', Db::prepareDateForDb($rangeStart), Db::prepareDateForDb($rangeEnd)])
            ->all();

        $licenses = [];

        foreach ($results as $result) {
            $licenses[] = new PluginLicense($result);
        }

        return $licenses;
    }

    /**
     * Returns any licenses that have expired by today but don't know it yet.
     *
     * @return PluginLicense[]
     */
    public function getFreshlyExpiredLicenses(): array
    {
        $tomorrow = (new \DateTime('midnight', new \DateTimeZone('UTC')))->modify('+1 days');
        $results = $this->_createLicenseQuery()
            ->andWhere([
                'expirable' => true,
                'expired' => false,
            ])
            ->andWhere(['not', ['expiresOn' => null]])
            ->andWhere(['<', 'expiresOn', Db::prepareDateForDb($tomorrow)])
            ->all();

        $licenses = [];

        foreach ($results as $result) {
            $licenses[] = new PluginLicense($result);
        }

        return $licenses;
    }

    /**
     * Saves a license.
     *
     * @param PluginLicense $license
     * @param bool $runValidation
     * @param array|null $attributes
     * @return bool if the license validated and was saved
     * @throws Exception if the license validated but didn't save
     * @throws \yii\db\Exception
     */
    public function saveLicense(PluginLicense $license, bool $runValidation = true, ?array $attributes = null): bool
    {
        if ($runValidation && !$license->validate()) {
            Craft::info('License not saved due to validation error.', __METHOD__);

            return false;
        }

        if (is_array($attributes)) {
            $attributes = array_flip($attributes);
        }

        if (!$license->pluginId) {
            $license->pluginId = (int)Plugin::find()
                ->select('elements.id')
                ->handle($license->pluginHandle)
                ->scalar();

            if ($license->pluginId === false) {
                throw new Exception("Invalid plugin handle: {$license->pluginHandle}");
            }

            if ($attributes !== null && isset($attributes['pluginHandle'])) {
                $attributes['pluginId'] = true;
            }
        }

        if (!$license->editionId && !$license->trial) {
            $license->editionId = (int)PluginEdition::find()
                ->select('elements.id')
                ->pluginId($license->pluginId)
                ->handle($license->edition)
                ->scalar();

            if ($license->editionId === false) {
                throw new Exception("Invalid plugin edition: {$license->edition}");
            }

            if ($attributes !== null && isset($attributes['edition'])) {
                $attributes['editionId'] = true;
            }
        }

        if ($license->trial) {
            $license->expirable = true;
            $license->expired = false;
            $license->autoRenew = false;
            $license->reminded = false;
            $license->renewalPrice = null;

            if ($attributes !== null && isset($attributes['trial'])) {
                $attributes['expirable'] = true;
                $attributes['expired'] = true;
                $attributes['autoRenew'] = true;
                $attributes['reminded'] = true;
                $attributes['renewalPrice'] = true;
            }
        } else if ($license->expirable) {
            if (!$license->renewalPrice) {
                $license->renewalPrice = $license->getEdition()->getRenewal()->getPrice();
                if ($attributes !== null) {
                    $attributes['renewalPrice'] = true;
                }
            }
        } else if ($license->renewalPrice !== null) {
            $license->renewalPrice = null;
            if ($attributes !== null) {
                $attributes['renewalPrice'] = true;
            }
        }

        $data = [
            'pluginId' => $license->pluginId,
            'editionId' => $license->editionId,
            'ownerId' => $license->ownerId,
            'cmsLicenseId' => $license->cmsLicenseId,
            'pluginHandle' => $license->pluginHandle,
            'edition' => $license->edition,
            'trial' => $license->trial,
            'expirable' => $license->expirable,
            'expired' => $license->expired,
            'autoRenew' => $license->autoRenew,
            'reminded' => $license->reminded,
            'renewalPrice' => $license->renewalPrice,
            'email' => $license->email,
            'key' => $license->key,
            'notes' => $license->notes,
            'privateNotes' => $license->privateNotes,
            'lastVersion' => $license->lastVersion,
            'lastAllowedVersion' => $license->lastAllowedVersion,
            'lastActivityOn' => Db::prepareDateForDb($license->lastActivityOn),
            'lastStatus' => $license->lastStatus,
            'lastRenewedOn' => Db::prepareDateForDb($license->lastRenewedOn),
            'expiresOn' => Db::prepareDateForDb($license->expiresOn),
            'dateCreated' => Db::prepareDateForDb($license->dateCreated),
        ];

        if (!$license->id) {
            $success = (bool)Db::insert(Table::PLUGINLICENSES, $data);

            // set the ID on the model
            $license->id = (int)Craft::$app->getDb()->getLastInsertID(Table::PLUGINLICENSES);
        } else {
            if ($attributes !== null) {
                $data = ArrayHelper::filter($data, array_keys($attributes));
            }
            $success = (bool)Db::update(Table::PLUGINLICENSES, $data, ['id' => $license->id]);
        }

        if (!$success) {
            throw new Exception('License validated but didn’t save.');
        }

        return true;
    }

    /**
     * Adds a new record to a Craft license’s history.
     *
     * @param int $licenseId
     * @param string $note
     * @param string|null $timestamp
     */
    public function addHistory(int $licenseId, string $note, string $timestamp = null)
    {
        Db::insert(Table::PLUGINLICENSEHISTORY, [
            'licenseId' => $licenseId,
            'note' => $note,
            'timestamp' => $timestamp ?? Db::prepareDateForDb(new \DateTime()),
        ]);
    }

    /**
     * Returns a license's history in chronological order.
     *
     * @param int $licenseId
     * @return array
     */
    public function getHistory(int $licenseId): array
    {
        return (new Query())
            ->select(['note', 'timestamp'])
            ->from(Table::PLUGINLICENSEHISTORY)
            ->where(['licenseId' => $licenseId])
            ->orderBy(['timestamp' => SORT_ASC])
            ->all();
    }

    /**
     * Claims a license for a user.
     *
     * @param User $user
     * @param string $key
     *
     * @throws LicenseNotFoundException
     * @throws Exception
     */
    public function claimLicense(User|Org $forOwner, User $byUser, string $key): void
    {
        $key = $this->normalizeKey($key);

        $result = $this->_createLicenseQuery()
            ->andWhere(['l.key' => $key])
            ->one();

        if ($result === null) {
            throw new LicenseNotFoundException($key);
        }

        $license = new PluginLicense($result);

        // make sure the license doesn't already have an owner
        if ($license->ownerId) {
            throw new Exception('License has already been claimed.');
        }

        $isOrg = $forOwner instanceof Org;
        $license->ownerId = $forOwner->id;
        $license->email = $byUser->email;

        if (!$this->saveLicense($license, true, [
            'ownerId',
            'email',
        ])) {
            throw new Exception('Could not save plugin license: ' . implode(', ', $license->getErrorSummary(true)));
        }

        $note = "claimed by $byUser->email";

        if ($isOrg) {
            $note .= " for organization $forOwner->title";
        }

        $this->addHistory($license->id, $note);    }

    /**
     * Finds unclaimed licenses that are associated with orders placed by the given user's email,
     * and assigns them to the user.
     *
     * @param User $user
     * @param string|null $email the email to look for (defaults to the user's email)
     * @return int the total number of affected licenses
     */
    public function claimLicenses(User $user, string $email = null): int
    {
        return Db::update(Table::PLUGINLICENSES, [
            'ownerId' => $user->id,
        ], [
            'and',
            ['ownerId' => null],
            new Expression('lower([[email]]) = :email', [':email' => strtolower($email ?? $user->email)]),
        ], updateTimestamp: false);
    }

    public function transferLicense(PluginLicense $license, User|Org $to, User $by): bool
    {
        $license->ownerId = $to->id;
        $license->email = $by->email;

        if (!$this->saveLicense($license, true, [
            'ownerId',
            'email',
        ])) {
            return false;
        }

        $toNote = $to instanceof Org ? "organization $to->title" : $to->email;
        $note = "transferred to $toNote by $by->email";

        $this->addHistory($license->id, $note);

        return true;
    }
    /**
     * Returns licenses by owner as an array.
     *
     * @param User $owner
     * @return array
     */
    public function getLicensesArrayByOwner(User|Org $owner)
    {
        $results = $this->getLicensesByOwner($owner);

        return $this->transformLicensesForOwner($results, $owner);
    }

    /**
     * Transforms licenses for the given owner.
     *
     * @param array $results
     * @param User $owner
     * @return array
     */
    public function transformLicensesForOwner(array $results, User|Org $owner)
    {
        $licenses = [];

        foreach ($results as $result) {
            $licenses[] = $this->transformLicenseForOwner($result, $owner);
        }

        return $licenses;
    }

    /**
     * Returns licenses by owner as an array.
     *
     * @param User $owner
     * @param string|null $searchQuery
     * @return int
     */
    public function getTotalLicensesByOwner(User|Org $owner, string $searchQuery = null): int
    {
        $licenseQuery = $this->_createLicenseQueryForOwner($owner, $searchQuery);

        return $licenseQuery->count();
    }

    /**
     * Transforms a license for the given owner.
     *
     * @param PluginLicense $result
     * @param User $owner
     * @return array
     */
    public function transformLicenseForOwner(PluginLicense $result, User|Org $owner)
    {
        if ($result->ownerId === $owner->id) {
            $license = $result->getAttributes(['id', 'editionId', 'key', 'cmsLicenseId', 'email', 'notes', 'autoRenew', 'expirable', 'expired', 'expiresOn', 'dateCreated']);
        } else {
            $license = [
                'shortKey' => $result->getShortKey(),
            ];
        }

        // History
        $license['history'] = $this->getHistory($result->id);

        // Edition details
        $license['edition'] = $result->editionId ? PluginEdition::findOne($result->editionId) : null;

        if (!empty($license['expiresOn'])) {
            // Expiry date options
            $expiryDateOptions = LicenseHelper::getExpiryDateOptions($license['expiresOn']);
            $renewalStart = max($license['expiresOn'], new \DateTime());

            // Renewal options
            $license['renewalOptions'] = [];

            foreach ($expiryDateOptions as $key => $expiryDateOption) {
                $optionDate = OrderHelper::expiryStr2Obj($expiryDateOption[1]);
                $paidRenewalYears = OrderHelper::dateDiffInYears($optionDate, $renewalStart);
                $amount = max(0, round($license['edition']->renewalPrice * $paidRenewalYears, 2));

                $license['renewalOptions'][$key] = [
                    'expiryDate' => $expiryDateOption[1],
                    'amount' => $amount,
                ];
            }
        }

        // Plugin
        $plugin = null;

        if ($result->pluginId) {
            /** @var Plugin $pluginResult */
            $pluginResult = Plugin::find()->id($result->pluginId)->status(null)->one();
            $plugin = $pluginResult->getAttributes(['name', 'handle']);
            $plugin['hasMultipleEditions'] = $pluginResult->getHasMultipleEditions();
        }

        $license['plugin'] = $plugin;

        // CMS License
        $cmsLicense = null;

        if ($result->cmsLicenseId) {
            $cmsLicenseResult = Module::getInstance()->getCmsLicenseManager()->getLicenseById($result->cmsLicenseId);

            if ($cmsLicenseResult->ownerId === $owner->id) {
                $cmsLicense = $cmsLicenseResult->getAttributes(['key', 'editionHandle']);
            } else {
                $cmsLicense = [
                    'shortKey' => substr($cmsLicenseResult->key, 0, 10),
                ];
            }
        }

        $license['cmsLicense'] = $cmsLicense;

        return $license;
    }

    /**
     * Deletes a license by its ID.
     *
     * @param int $id
     * @throws LicenseNotFoundException if $key is missing
     */
    public function deleteLicenseById(int $id)
    {
        $rows = Db::delete(Table::PLUGINLICENSES, ['id' => $id]);

        if ($rows === 0) {
            throw new LicenseNotFoundException($id);
        }
    }

    /**
     * Deletes a license by its key.
     *
     * @param string $key
     * @throws LicenseNotFoundException if $key is missing
     */
    public function deleteLicenseByKey(string $key)
    {
        try {
            $key = $this->normalizeKey($key);
        } catch (InvalidArgumentException $e) {
            throw new LicenseNotFoundException($key, $e->getMessage(), 0, $e);
        }

        $rows = Db::delete(Table::PLUGINLICENSES, ['key' => $key]);

        if ($rows === 0) {
            throw new LicenseNotFoundException($key);
        }
    }

    /**
     * Returns the number of licenses expiring in the next 45 days.
     *
     * @param User $owner
     * @return int
     * @throws \Exception
     */
    public function getExpiringLicensesTotal(User|Org $owner): int
    {
        $date = new \DateTime('now');
        $date->add(new \DateInterval('P45D'));
        $dateFormatted = $date->format('Y-m-d');

        $licenseQuery = $this->_createLicenseQuery()
            ->andWhere(['l.ownerId' => $owner->id])
            ->andWhere(['l.expired' => false])
            ->andWhere(['l.autoRenew' => false])
            ->andWhere(['not', ['l.expiresOn' => null]])
            ->andWhere(['<=', 'l.expiresOn', $dateFormatted]);

        return $licenseQuery->count();
    }

    /**
     * @param bool $anyStatus whether to include licenses for disabled editions
     * @param bool $includeTrials whether to include trial licenses
     * @param bool $includeFreeEditions whether to include licenses for free editions
     * @return Query
     */
    private function _createLicenseQuery(bool $anyStatus = false, bool $includeTrials = false, bool $includeFreeEditions = false): Query
    {
        $query = (new Query())
            ->select([
                'l.id',
                'l.pluginId',
                'l.editionId',
                'l.ownerId',
                'l.cmsLicenseId',
                'l.pluginHandle',
                'l.edition',
                'l.trial',
                'l.expirable',
                'l.expired',
                'l.autoRenew',
                'l.reminded',
                'l.renewalPrice',
                'l.email',
                'l.key',
                'l.notes',
                'l.privateNotes',
                'l.lastVersion',
                'l.lastAllowedVersion',
                'l.lastActivityOn',
                'l.lastStatus',
                'l.lastRenewedOn',
                'l.expiresOn',
                'l.dateCreated',
                'l.dateUpdated',
                'l.uid',
            ])
            ->from(['l' => Table::PLUGINLICENSES])
            // exclude licenses for plugin editions that are now free
            ->leftJoin(['e' => Table::PLUGINEDITIONS], ['and', '[[e.id]] = [[l.editionId]]'])
            ->where(array_filter([
                'or',
                ['not', ['e.id' => null]],
                $includeTrials ? ['l.trial' => true] : false,
            ]));

        if (!$anyStatus) {
            $query
                ->innerJoin('elements pl_el', ['and', '[[pl_el.id]] = [[l.pluginId]]', ['pl_el.enabled' => true]])
                ->leftJoin('elements ed_el', ['and', '[[ed_el.id]] = [[l.editionId]]', ['ed_el.enabled' => true]])
                ->andWhere(array_filter([
                    'or',
                    ['not', ['ed_el.id' => null]],
                    $includeTrials ? ['l.trial' => true] : false,
                ]));
        }

        if (!$includeTrials) {
            $query->andWhere(['l.trial' => false]);
        }

        if (!$includeFreeEditions) {
            $query->andWhere(array_filter([
                'or',
                ['not', ['e.price' => 0]],
                $includeTrials ? ['l.trial' => true] : false,
            ]));
        }

        return $query;
    }

    /**
     * @param User $owner
     * @param string|null $searchQuery
     * @return Query
     */
    private function _createLicenseQueryForOwner(User|Org $owner, string $searchQuery = null)
    {
        $query = $this->_createLicenseQuery()
            ->andWhere(['l.ownerId' => $owner->id]);

        if ($searchQuery) {
            $query->andWhere([
                'or',
                ['ilike', 'l.key', $searchQuery],
                ['ilike', 'l.notes', $searchQuery],
                ['ilike', 'l.pluginHandle', $searchQuery],
                ['ilike', 'l.email', $searchQuery],
            ]);
        }

        return $query;
    }
}
