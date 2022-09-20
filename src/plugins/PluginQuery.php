<?php

namespace craftnet\plugins;

use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craftnet\db\Table;
use craftnet\Module;
use Illuminate\Support\Collection;
use yii\db\Connection;
use yii\helpers\ArrayHelper;

/**
 * @method Plugin[]|array all($db = null)
 * @method Plugin|array|null one($db = null)
 * @method Plugin|array|null nth(int $n, Connection $db = null)
 */
class PluginQuery extends ElementQuery
{
    /**
     * @var string|string[]|null The handle(s) that the resulting plugins must have.
     */
    public $handle;

    /**
     * @var string|string[]|null The license(s) that the resulting plugins must have.
     */
    public $license;

    /**
     * @var int|int[]|null The category ID(s) that the resulting plugins must have.
     */
    public $categoryId;

    /**
     * @var int|int[]|null The user ID(s) that the resulting plugins’ developers must have.
     */
    public $developerId;

    /**
     * @var int|int[]|null The Composer package ID(s) that the resulting plugins must be associated with.
     */
    public $packageId;

    /**
     * @var bool|null Whether to fetch abandoned plugins.
     */
    public ?bool $abandoned = null;

    /**
     * @var bool Whether info about the latest release should be included
     */
    public $withLatestReleaseInfo = false;

    /**
     * @var string|string[]|null Craft version the latest release must be compatible with
     */
    public string|array|null $cmsVersion = null;

    /**
     * @var string|null Minimum stability the latest release must have
     */
    public $minStability;

    /**
     * @var bool Whether a stable release should be returned if possible
     */
    public $preferStable = true;

    /**
     * @var bool Whether info about the total purchases should be included
     */
    public $withTotalPurchases = false;

    /**
     * @var \DateTime How for back to look for total purchases
     */
    public $totalPurchasesSince;

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = Table::PLUGINS . '.name';
        }

        parent::__construct($elementType, $config);
    }

    /**
     * Sets the [[handle]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function handle($value)
    {
        $this->handle = $value;
        return $this;
    }

    /**
     * Sets the [[license]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function license($value)
    {
        $this->license = $value;
        return $this;
    }

    /**
     * Sets the [[categoryId]] property.
     *
     * @param int|int[]|null $value The property value
     *
     * @return static self reference
     */
    public function categoryId($value)
    {
        $this->categoryId = $value;
        return $this;
    }

    /**
     * Sets the [[developerId]] property.
     *
     * @param int|int[]|null $value The property value
     *
     * @return static self reference
     */
    public function developerId($value)
    {
        $this->developerId = $value;
        return $this;
    }

    /**
     * Sets the [[packageId]] property.
     *
     * @param int|int[]|null $value The property value
     *
     * @return static self reference
     */
    public function packageId($value)
    {
        $this->packageId = $value;
        return $this;
    }

    /**
     * Sets the [[abandoned]] property.
     *
     * @param bool|null $value The property value
     * @return static self reference
     */
    public function abandoned(?bool $value = true): static
    {
        $this->abandoned = $value;
        return $this;
    }

    /**
     * Sets the [[withLatestReleaseInfo]], [[cmsVersion]], and [[minStability]] properties.
     *
     * @param bool $withLatestReleaseInfo
     * @param string|array|null $cmsVersion
     * @param string|null $minStability
     * @param bool $preferStable
     * @return static self reference
     */
    public function withLatestReleaseInfo(
        bool $withLatestReleaseInfo = true,
        string|array|null $cmsVersion = null,
        ?string $minStability = null,
        bool $preferStable = true,
    ): static {
        $this->withLatestReleaseInfo = $withLatestReleaseInfo;
        $this->cmsVersion = $cmsVersion;
        $this->minStability = $minStability;
        $this->preferStable = $preferStable;
        return $this;
    }

    /**
     * Sets the [[cmsVersion]] property.
     *
     * @param string|string[]|null $value
     * @return static self reference
     */
    public function cmsVersion(string|array|null $value): static
    {
        $this->withLatestReleaseInfo = true;
        $this->cmsVersion = $value;
        return $this;
    }

    /**
     * Sets the [[minStability]] property.
     *
     * @param string|null $value
     * @return static self reference
     */
    public function minStability(?string $value): static
    {
        $this->withLatestReleaseInfo = true;
        $this->minStability = $value;
        return $this;
    }

    /**
     * Sets the [[preferStable]] property.
     *
     * @param bool $value
     * @return static self reference
     */
    public function preferStable(bool $value = true): static
    {
        $this->withLatestReleaseInfo = true;
        $this->preferStable = $value;
        return $this;
    }

    /**
     * Sets the [[withTotalPurchases]] and [[totalPurchasesSince]] properties.
     *
     * @param bool $withTotalPurchases
     * @param \DateTime|null $since
     * @return static self reference
     */
    public function withTotalPurchases(bool $withTotalPurchases = true, \DateTime $since = null)
    {
        $this->withTotalPurchases = $withTotalPurchases;
        $this->totalPurchasesSince = $since;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        if ($this->cmsVersion === '0.0') {
            return false;
        }

        $this->joinElementTable('craftnet_plugins');

        $this->query->select([
            Table::PLUGINS . '.developerId',
            Table::PLUGINS . '.packageId',
            Table::PLUGINS . '.iconId',
            Table::PLUGINS . '.packageName',
            Table::PLUGINS . '.repository',
            Table::PLUGINS . '.name',
            Table::PLUGINS . '.handle',
            Table::PLUGINS . '.license',
            Table::PLUGINS . '.shortDescription',
            Table::PLUGINS . '.longDescription',
            Table::PLUGINS . '.documentationUrl',
            Table::PLUGINS . '.changelogPath',
            Table::PLUGINS . '.activeInstalls',
            Table::PLUGINS . '.pendingApproval',
            Table::PLUGINS . '.keywords',
            Table::PLUGINS . '.dateApproved',
            Table::PLUGINS . '.published',
            Table::PLUGINS . '.abandoned',
            Table::PLUGINS . '.replacementId',
        ]);

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam(Table::PLUGINS . '.handle', $this->handle));
        }

        if ($this->license) {
            $this->subQuery->andWhere(Db::parseParam(Table::PLUGINS . '.license', $this->license));
        }

        if ($this->developerId) {
            $this->subQuery->andWhere(Db::parseParam(Table::PLUGINS . '.developerId', $this->developerId));
        }

        if ($this->packageId) {
            $this->subQuery->andWhere(Db::parseParam(Table::PLUGINS . '.packageId', $this->packageId));
        }

        if ($this->abandoned !== null) {
            $this->subQuery->andWhere([Table::PLUGINS . '.abandoned' => $this->abandoned]);
        }

        if ($this->categoryId) {
            $this->subQuery
                ->innerJoin(['pc' => Table::PLUGINCATEGORIES], '[[pc.pluginId]] = [[elements.id]]')
                ->andWhere(Db::parseParam('pc.categoryId', $this->categoryId));
        }

        if ($this->withLatestReleaseInfo) {
            $maxCol = $this->preferStable ? 'stableOrder' : 'order';
            $latestReleaseQuery = (new Query())
                ->select(["max([[s_vo.{$maxCol}]])"])
                ->from(['s_vo' => Table::PLUGINVERSIONORDER])
                ->innerJoin(['s_v' => Table::PACKAGEVERSIONS], '[[s_v.id]] = [[s_vo.versionId]]')
                ->where('[[s_v.packageId]] = [[craftnet_plugins.packageId]]')
                ->groupBy(['s_v.packageId']);

            $packageManager = Module::getInstance()->getPackageManager();

            if ($this->cmsVersion) {
                $cmsReleaseIds = [];
                foreach ((array)$this->cmsVersion as $cmsVersion) {
                    $cmsReleaseId = $packageManager->getRelease('craftcms/cms', $cmsVersion)?->id;
                    if ($cmsReleaseId) {
                        $cmsReleaseIds[] = $cmsReleaseId;
                    }
                }
                if (empty($cmsReleaseIds)) {
                    return false;
                }
                $latestReleaseQuery
                    ->innerJoin(['s_vc' => Table::PLUGINVERSIONCOMPAT], '[[s_vc.pluginVersionId]] = [[s_v.id]]')
                    ->andWhere(['s_vc.cmsVersionId' => count($cmsReleaseIds) === 1 ? $cmsReleaseIds[0] : $cmsReleaseIds]);
            }

            if ($this->minStability) {
                $latestReleaseQuery->andWhere([
                    's_v.stability' => $packageManager->getStabilities($this->minStability),
                ]);
            }

            $this->subQuery
                ->addSelect([
                    'latestVersionId' => 'v.id',
                    'latestVersion' => 'v.version',
                    'latestVersionTime' => 'v.time',
                ])
                ->innerJoin(['v' => Table::PACKAGEVERSIONS], '[[v.packageId]] = [[craftnet_plugins.packageId]]')
                ->innerJoin(['vo' => Table::PLUGINVERSIONORDER], '[[vo.versionId]] = [[v.id]]')
                ->andWhere(["vo.{$maxCol}" => $latestReleaseQuery]);
            $this->query
                ->addSelect(['latestVersionId', 'latestVersion', 'latestVersionTime']);
        }

        if ($this->withTotalPurchases) {
            $totalPurchasesSubquery = (new Query())
                ->select(['count(*)'])
                ->from(['p' => Table::PLUGINS])
                ->innerJoin(['pl' => Table::PLUGINLICENSES], '[[pl.pluginId]] = [[p.id]]')
                ->innerJoin(['pl_li' => Table::PLUGINLICENSES_LINEITEMS], '[[pl_li.licenseId]] = [[pl.id]]')
                ->where('[[p.id]] = [[craftnet_plugins.id]]');

            if ($this->totalPurchasesSince) {
                $totalPurchasesSubquery->andWhere(['>=', 'pl.dateCreated', Db::prepareDateForDb($this->totalPurchasesSince)]);
            }

            $this->subQuery
                ->addSelect([
                    'totalPurchases' => $totalPurchasesSubquery,
                ]);
            $this->query
                ->addSelect(['totalPurchases']);
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    public function afterPopulate(array $elements): array
    {
        if ($this->cmsVersion && !is_array($this->cmsVersion)) {
            foreach ($elements as $element) {
                if ($element instanceof Plugin) {
                    $element->compatibleCmsVersion = $this->cmsVersion;
                }
            }
        }

        return parent::afterPopulate($elements);
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status): mixed
    {
        switch ($status) {
            case Plugin::STATUS_PENDING:
                return [CraftTable::ELEMENTS . '.enabled' => false, Table::PLUGINS . '.pendingApproval' => true];
            case Plugin::STATUS_ABANDONED:
                return [CraftTable::ELEMENTS . '.enabled' => true, Table::PLUGINS . '.abandoned' => true];
        }

        return parent::statusCondition($status);
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->handle && (is_string($this->handle) || is_array($this->handle))) {
            if (is_string($this->handle)) {
                $handles = StringHelper::split($this->handle);
            } else {
                $handles = $this->handle;
            }

            if (Collection::make($handles)->every(fn($v) => preg_match(Plugin::HANDLE_PATTERN, $v))) {
                foreach ($handles as $handle) {
                    $tags[] = $handle;
                }
            }
        }

        return $tags;
    }
}
