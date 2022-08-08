<?php

namespace craftnet\plugins;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\SetStatus;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\validators\UniqueValidator;
use craftnet\behaviors\UserBehavior;
use craftnet\composer\Package;
use craftnet\db\Table;
use craftnet\Module;
use craftnet\records\Plugin as PluginRecord;
use DateTime;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;

/**
 * @property PluginEdition[] $editions
 * @property string $eagerLoadedElements
 * @property-read Asset|null $icon
 * @property-read bool $hasMultipleEditions
 * @property-read Package $package
 * @property-read User $developer
 */
class Plugin extends Element
{
    const STATUS_PENDING = 'pending';
    const STATUS_ABANDONED = 'abandoned';

    const HANDLE_PATTERN = '/^[a-z]([a-z0-9\-]*[a-z0-9])?$/';

    /**
     * @event Event The event that is triggered when the plugin is first published to the Plugin Store.
     */
    const EVENT_PUBLISHED = 'published';

    /**
     * @return string
     */
    public static function displayName(): string
    {
        return 'Plugin';
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ENABLED => Craft::t('app', 'Enabled'),
            self::STATUS_PENDING => Craft::t('app', 'Pending Approval'),
            self::STATUS_DISABLED => Craft::t('app', 'Disabled'),
        ];
    }

    /**
     * @return PluginQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new PluginQuery(static::class);
    }

    /**
     * @param ElementQueryInterface $elementQuery
     * @param array|null $disabledElementIds
     * @param array $viewState
     * @param string|null $sourceKey
     * @param string|null $context
     * @param bool $includeContainer
     * @param bool $showCheckboxes
     *
     * @return string
     */
    public static function indexHtml(ElementQueryInterface $elementQuery, ?array $disabledElementIds = null, array $viewState, ?string $sourceKey = null, ?string $context = null, bool $includeContainer, bool $showCheckboxes): string
    {
        /** @var PluginQuery $elementQuery */
        $elementQuery
            ->withLatestReleaseInfo(true, null, null, false)
            ->with(['icon', 'primaryCategory']);

        return parent::indexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        switch ($handle) {
            case 'editions':
                $query = (new Query())
                    ->select([
                        'source' => 'pluginId',
                        'target' => 'id',
                    ])
                    ->from([Table::PLUGINEDITIONS])
                    ->where(['pluginId' => ArrayHelper::getColumn($sourceElements, 'id')])
                    ->orderBy(['price' => SORT_ASC]);
                return ['elementType' => PluginEdition::class, 'map' => $query->all()];

            case 'developer':
                $query = (new Query())
                    ->select([
                        'source' => 'id',
                        'target' => 'developerId',
                    ])
                    ->from([Table::PLUGINS])
                    ->where(['id' => ArrayHelper::getColumn($sourceElements, 'id')]);
                return ['elementType' => User::class, 'map' => $query->all()];

            case 'icon':
                $query = (new Query())
                    ->select([
                        'source' => 'id',
                        'target' => 'iconId',
                    ])
                    ->from([Table::PLUGINS])
                    ->where(['id' => ArrayHelper::getColumn($sourceElements, 'id')])
                    ->andWhere(['not', ['iconId' => null]]);
                return ['elementType' => Asset::class, 'map' => $query->all()];

            case 'categories':
            case 'primaryCategory':
                $query = (new Query())
                    ->select([
                        'source' => 'p.id',
                        'target' => 'pc.categoryId',
                    ])
                    ->from(['p' => Table::PLUGINS])
                    ->innerJoin(['pc' => Table::PLUGINCATEGORIES], '[[pc.pluginId]] = [[p.id]]')
                    ->where(['p.id' => ArrayHelper::getColumn($sourceElements, 'id')])
                    ->orderBy(['pc.sortOrder' => SORT_ASC]);
                if ($handle === 'primaryCategory') {
                    $query->andWhere(['pc.sortOrder' => 1]);
                }
                return ['elementType' => Category::class, 'map' => $query->all()];

            case 'screenshots':
                $query = (new Query())
                    ->select([
                        'source' => 'p.id',
                        'target' => 'ps.assetId',
                    ])
                    ->from(['p' => Table::PLUGINS])
                    ->innerJoin(['ps' => Table::PLUGINSCREENSHOTS], '[[ps.pluginId]] = [[p.id]]')
                    ->where(['p.id' => ArrayHelper::getColumn($sourceElements, 'id')])
                    ->orderBy(['ps.sortOrder' => SORT_ASC]);
                return ['elementType' => Asset::class, 'map' => $query->all()];

            case 'replacement':
                $query = (new Query())
                    ->select([
                        'source' => 'id',
                        'target' => 'replacementId',
                    ])
                    ->from([Table::PLUGINS])
                    ->where(['id' => ArrayHelper::getColumn($sourceElements, 'id')])
                    ->andWhere(['not', ['replacementId' => null]]);
                return ['elementType' => Plugin::class, 'map' => $query->all()];

            default:
                return parent::eagerLoadingMap($sourceElements, $handle);
        }
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => 'All Plugins',
                'criteria' => ['status' => null],
            ],
            [
                'heading' => 'Categories',
            ],
        ];

        /** @var Category[] $categories */
        $categories = Category::find()
            ->group('pluginCategories')
            ->with('icon')
            ->all();
        $assetsService = Craft::$app->getAssets();

        foreach ($categories as $category) {
            $sources[] = [
                'key' => 'category:' . $category->id,
                'label' => $category->title,
                'criteria' => ['categoryId' => $category->id],
            ];
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        return [
            SetStatus::class,
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            'developerName',
            'packageName',
            'repository',
            'name',
            'handle',
            'license',
            'shortDescription',
            'longDescription',
            'keywords',
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'name' => 'Name',
            'handle' => 'Handle',
            'packageName' => 'Package Name',
            'repository' => 'Repository',
            'license' => 'License',
            'primaryCategory' => 'Primary Category',
            'documentationUrl' => 'Documentation URL',
            'latestVersion' => 'Version',
            'latestVersionTime' => 'Last Update',
            'activeInstalls' => 'Installs',
            'keywords' => 'Keywords',
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'name',
            'handle',
            'packageName',
            'repository',
            'license',
            'primaryCategory',
        ];
    }

    /**
     * @var bool Whether the element is enabled
     */
    public bool $enabled = false;

    /**
     * @var bool Whether the plugin has been published on the Plugin Store.
     */
    public bool $published = false;

    /**
     * @var int|null The developer’s user ID
     */
    public ?int $developerId = null;

    /**
     * @var null|int The Composer package ID
     */
    public ?int $packageId = null;

    /**
     * @var int|null The icon asset’s ID
     */
    public ?int $iconId = null;

    /**
     * @var string|null Composer package name
     */
    public ?string $packageName = null;

    /**
     * @var null|string The VCS repository URL
     */
    public ?string $repository = null;

    /**
     * @var null|string The plugin name
     */
    public ?string $name = null;

    /**
     * @var null|string The plugin handle
     */
    public ?string $handle = null;

    /**
     * @var string The license type ('mit', 'craft')
     */
    public string $license = 'craft';

    /**
     * @var string|null The plugin’s short description
     */
    public ?string $shortDescription = null;

    /**
     * @var string|null The plugin’s long description
     */
    public ?string $longDescription = null;

    /**
     * @var string|null The plugin’s documentation URL
     */
    public ?string $documentationUrl = null;

    /**
     * @var string|null The plugin’s changelog path
     */
    public ?string $changelogPath = null;

    /**
     * @var int|null The ID of the latest version available for the plugin
     */
    public ?int $latestVersionId = null;

    /**
     * @var string|null The latest version available for the plugin
     */
    public ?string $latestVersion = null;

    /**
     * @var DateTime|null The release time of the latest version
     */
    public ?DateTime $latestVersionTime = null;

    /**
     * @var string|null The Craft CMS version that the latest version must have been compatible with
     */
    public ?string $compatibleCmsVersion = null;

    /**
     * @var int The number of active installs.
     */
    public int $activeInstalls = 0;

    /**
     * @var string|null
     */
    public ?string $devComments = null;

    /**
     * @var bool Whether the plugin is pending approval.
     */
    public bool $pendingApproval = false;

    /**
     * @var string|null
     */
    public ?string $keywords = null;

    /**
     * @var DateTime|null The date that the plugin was approved
     */
    public ?DateTime $dateApproved = null;

    /**
     * @var int|null The total number of times this plugin has been purchased.
     */
    public ?int $totalPurchases = null;

    /**
     * @var bool Whether the plugin is abandoned
     */
    public bool $abandoned = false;

    /**
     * @var int|null The replacement plugin’s ID
     */
    public ?int $replacementId = null;

    /**
     * @var PluginEdition[]|null
     */
    private ?array $_editions = null;

    /**
     * @var PluginEdition[]|null All editions regardless of status
     */
    private ?array $_allEditions = null;

    /**
     * @var User|null
     */
    private ?User $_developer = null;

    /**
     * @var Package|null
     */
    private ?Package $_package = null;

    /**
     * @var Asset|null
     */
    private ?Asset $_icon = null;

    /**
     * @var Category[]|null
     */
    private ?array $_categories = null;

    /**
     * @var Asset[]|null
     */
    private ?array $_screenshots = null;

    /**
     * @var Plugin|null
     */
    private ?Plugin $_replacement = null;

    /**
     * @var bool Whether the plugin was just submitted for approval
     */
    private bool $_submittedForApproval = false;

    /**
     * @var bool Whether the plugin was just approved
     * @see approve()
     */
    private bool $_approved = false;

    /**
     * @var bool Whether changes have been requested
     * @see requestChanges()
     */
    private bool $_changesRequested = false;

    /**
     * @var bool Whether the plugin has been fully rejected
     * @see reject()
     */
    private bool $_rejected = false;

    /**
     * @var PluginHistory|null
     */
    private ?PluginHistory $_history = null;

    /**
     * @var string|null
     */
    private ?string $_oldHandle = null;

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->_oldHandle = $this->handle;
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        ArrayHelper::removeValue($names, 'activeInstalls');
        ArrayHelper::removeValue($names, 'devComments');
        $names[] = 'developerName';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $fields = parent::extraFields();
        $fields[] = 'icon';
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateApproved';
        $attributes[] = 'latestVersionTime';
        return $attributes;
    }

    /**
     * @param string $handle
     * @param array $elements
     */
    public function setEagerLoadedElements(string $handle, array $elements): void
    {
        switch ($handle) {
            case 'editions':
                $this->_editions = $elements;
                break;
            case 'developer':
                $this->_developer = $elements[0] ?? null;
                break;
            case 'icon':
                $this->_icon = $elements[0] ?? null;
                break;
            case 'categories':
            case 'primaryCategory':
                $this->setCategories($elements);
                break;
            case 'screenshots':
                $this->setScreenshots($elements);
                break;
            case 'replacement':
                $this->_replacement = $elements[0] ?? null;
                break;
            default:
                parent::setEagerLoadedElements($handle, $elements);
        }
    }

    /**
     * @return bool
     * @throws InvalidConfigException
     */
    public function getHasMultipleEditions(): bool
    {
        if ($this->_editions !== null) {
            $count = count($this->_editions);
        } else {
            if ($this->id === null) {
                throw new InvalidConfigException('Plugin is missing its ID.');
            }

            $count = PluginEdition::find()
                ->pluginId($this->id)
                ->count();
        }

        return $count > 1;
    }

    /**
     * @param bool $anyStatus
     * @return PluginEdition[]
     * @throws InvalidConfigException
     */
    public function getEditions(bool $anyStatus = false): array
    {
        if (!$anyStatus && $this->_editions !== null) {
            return $this->_editions;
        }
        if ($anyStatus && $this->_allEditions !== null) {
            return $this->_allEditions;
        }
        if ($this->id === null) {
            throw new InvalidConfigException('Plugin is missing its ID.');
        }

        $query = PluginEdition::find()
            ->pluginId($this->id);

        if ($anyStatus) {
            $query->status(null);
        }

        $editions = $query->all();

        if (!$anyStatus) {
            $this->_editions = $editions;
        } else {
            $this->_allEditions = $editions;
        }

        return $editions;
    }

    /**
     * @param PluginEdition[] $editions
     */
    public function setEditions(array $editions)
    {
        $this->_allEditions = $editions;
    }

    /**
     * @param string $handle
     * @return PluginEdition
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     */
    public function getEdition(string $handle): PluginEdition
    {
        if ($this->id === null) {
            throw new InvalidConfigException('Plugin is missing its ID.');
        }

        foreach ($this->getEditions(true) as $edition) {
            if ($edition->handle === $handle) {
                return $edition;
            }
        }

        throw new InvalidArgumentException("Invalid plugin edition: {$handle}");
    }

    /**
     * @return User
     * @throws InvalidConfigException
     */
    public function getDeveloper(): User
    {
        if ($this->_developer !== null) {
            return $this->_developer;
        }
        if ($this->developerId === null) {
            throw new InvalidConfigException('Plugin is missing its developer ID');
        }
        /** @var User|null $user */
        $user = User::find()->id($this->developerId)->status(null)->one();
        if ($user === null) {
            throw new InvalidConfigException('Invalid developer ID: ' . $this->developerId);
        }
        return $this->_developer = $user;
    }

    /**
     * @return Package
     * @throws InvalidConfigException
     */
    public function getPackage(): Package
    {
        if ($this->_package !== null) {
            return $this->_package;
        }
        if ($this->packageId === null) {
            throw new InvalidConfigException('Plugin is missing its package ID');
        }
        return $this->_package = Module::getInstance()->getPackageManager()->getPackageById($this->packageId);
    }

    /**
     * @return string
     */
    public function getDeveloperName(): string
    {
        /** @var User|UserBehavior $developer */
        $developer = $this->getDeveloper();
        return $developer->getDeveloperName();
    }

    /**
     * @return Asset|null
     * @throws InvalidConfigException
     */
    public function getIcon()
    {
        if ($this->_icon === null) {
            if ($this->iconId === null) {
                return null;
            }
            /** @var Asset|null $icon */
            $icon = Asset::find()->id($this->iconId)->one();
            $this->_icon = $icon ?? false;
        }

        return $this->_icon ?: null;
    }

    /**
     * @return Category[]
     */
    public function getCategories(): array
    {
        if ($this->_categories !== null) {
            return $this->_categories;
        }
        /** @var Category[] $categories */
        $categories = Category::find()
            ->innerJoin(['pc' => Table::PLUGINCATEGORIES], [
                'and',
                '[[pc.categoryId]] = [[categories.id]]',
                ['pc.pluginId' => $this->id],
            ])
            ->orderBy(['pc.sortOrder' => SORT_ASC])
            ->all();
        return $this->_categories = $categories;
    }

    /**
     * @param Category[] $categories
     */
    public function setCategories(array $categories)
    {
        $this->_categories = $categories;
    }

    /**
     * @return Asset[]
     */
    public function getScreenshots(): array
    {
        if ($this->_screenshots !== null) {
            return $this->_screenshots;
        }
        /** @var Asset[] $screenshots */
        $screenshots = Asset::find()
            ->innerJoin(['ps' => Table::PLUGINSCREENSHOTS], [
                'and',
                '[[ps.assetId]] = [[assets.id]]',
                ['ps.pluginId' => $this->id],
            ])
            ->orderBy(['ps.sortOrder' => SORT_ASC])
            ->all();
        return $this->_screenshots = $screenshots;
    }

    /**
     * @param Asset[] $screenshots
     */
    public function setScreenshots(array $screenshots)
    {
        $this->_screenshots = $screenshots;
    }

    /**
     *
     */
    public function submitForApproval()
    {
        $this->_submittedForApproval = true;
        $this->pendingApproval = true;
        $this->enabled = false;
    }

    /**
     *
     */
    public function approve()
    {
        $this->_approved = true;
        $this->enabled = true;
    }

    /**
     *
     */
    public function requestChanges()
    {
        $this->_changesRequested = true;
        $this->enabled = false;
    }

    /**
     *
     */
    public function reject()
    {
        $this->_rejected = true;
        $this->enabled = false;
    }

    /**
     * @return PluginHistory
     */
    public function getHistory(): PluginHistory
    {
        if ($this->_history !== null) {
            return $this->_history;
        }
        return $this->_history = new PluginHistory($this);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            [
                'developerId',
                'packageName',
                'repository',
                'name',
                'handle',
                'license',
            ],
            'required',
        ];

        $rules[] = [
            [
                'id',
                'developerId',
                'packageId',
                'iconId',
            ],
            'number',
            'integerOnly' => true,
        ];

        $rules[] = [
            [
                'repository',
                'documentationUrl',
            ],
            'url',
        ];

        $rules[] = [
            [
                'categories',
            ],
            'required',
            'on' => self::SCENARIO_LIVE,
        ];

        $rules[] = [
            [
                'name',
            ],
            UniqueValidator::class,
            'targetClass' => PluginRecord::class,
            'targetAttribute' => ['name'],
            'message' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
        ];

        $rules[] = [
            [
                'handle',
            ],
            UniqueValidator::class,
            'targetClass' => PluginRecord::class,
            'targetAttribute' => ['handle'],
            'message' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
        ];

        $rules[] = [['handle'], 'validateHandle'];

        $rules[] = [
            [
                'packageName',
            ],
            UniqueValidator::class,
            'targetClass' => PluginRecord::class,
            'targetAttribute' => ['packageName'],
            'message' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
        ];

        return $rules;
    }

    /**
     * Validates the plugin handle.
     */
    public function validateHandle()
    {
        $this->handle = mb_strtolower($this->handle);

        if (!preg_match(self::HANDLE_PATTERN, $this->handle)) {
            $this->addError('handle', "“{$this->handle}” isn’t a valid plugin handle.");
            return;
        }

        if (in_array($this->handle, [
            'craft',
            'search',
            'favorites',
            'reactions',
        ], true)) {
            $this->addError('handle', "“{$this->handle}” is a reserved word.");
            return;
        }
    }

    /**
     * @inheritdoc
     */
    public function afterValidate(): void
    {
        parent::afterValidate();

        $editions = $this->getEditions(true);

        if (empty($editions)) {
            $this->addError('editions', Craft::t('yii', '{attribute} cannot be blank.', [
                'attribute' => $this->getAttributeLabel('editions'),
            ]));
            return;
        }

        $editionScenario = Craft::$app->getRequest()->getIsCpRequest() ? PluginEdition::SCENARIO_CP : PluginEdition::SCENARIO_SITE;

        foreach ($editions as $i => $edition) {
            $edition->setScenario($editionScenario);
            if (!$edition->validate()) {
                $this->addModelErrors($edition, "editions[$i]");
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        parent::validate($attributeNames, $clearErrors);

        if (($this->_changesRequested || $this->_rejected) && !$this->devComments) {
            $this->addError('devComments', 'You must explain why the plugin wasn’t approved.');
        }

        if ($this->hasErrors() && $this->pendingApproval) {
            // Undo the enabled=true
            $this->enabled = false;
        }

        return !$this->hasErrors();
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [
            $this->handle,
        ];

        // Did the handle just change?
        if ($this->handle !== $this->_oldHandle) {
            $tags[] = $this->_oldHandle;
        }

        return $tags;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        $packageManager = Module::getInstance()->getPackageManager();
        if ($packageManager->packageExists($this->packageName)) {
            $package = $packageManager->getPackage($this->packageName);
            if ($package->type !== 'craft-plugin' || $package->developerId != $this->developerId || $package->repository !== $this->repository || !$package->managed) {
                $package->type = 'craft-plugin';
                $package->developerId = $this->developerId;
                $package->repository = $this->repository;
                $package->managed = true;
                $packageManager->savePackage($package);
            }
        } else {
            $package = new Package([
                'developerId' => $this->developerId,
                'name' => $this->packageName,
                'type' => 'craft-plugin',
                'repository' => $this->repository,
                'managed' => true,
            ]);
            $packageManager->savePackage($package);
            $packageManager->updatePackage($package->name, false, true, true);
        }

        $this->packageId = $package->id;

        if ($this->_approved || $this->_changesRequested || $this->_rejected || $this->enabled) {
            $this->pendingApproval = false;
        }

        $pluginData = [
            'id' => $this->id,
            'developerId' => $this->developerId,
            'packageId' => $this->packageId,
            'iconId' => $this->iconId,
            'packageName' => $this->packageName,
            'repository' => $this->repository,
            'name' => $this->name,
            'handle' => $this->handle,
            'license' => $this->license,
            'shortDescription' => $this->shortDescription,
            'longDescription' => $this->longDescription,
            'documentationUrl' => $this->documentationUrl,
            'changelogPath' => $this->changelogPath ?: null,
            'pendingApproval' => $this->pendingApproval,
            'keywords' => $this->keywords,
            'abandoned' => $this->abandoned,
            'replacementId' => $this->replacementId,
        ];

        if ($this->_approved) {
            $pluginData['dateApproved'] = Db::prepareDateForDb(new DateTime('now', new \DateTimeZone('UTC')));
        }

        $categoryData = [];
        foreach ($this->getCategories() as $i => $category) {
            $categoryData[] = [$this->id, $category->id, $i + 1];
        }

        $screenshotData = [];
        foreach ($this->getScreenshots() as $i => $screenshot) {
            $screenshotData[] = [$this->id, $screenshot->id, $i + 1];
        }

        $db = Craft::$app->getDb();

        if ($isNew) {
            // Save a new row in the plugins table
            Db::insert(Table::PLUGINS, $pluginData);
        } else {
            // Update the plugins table row
            Db::update(Table::PLUGINS, $pluginData, ['id' => $this->id]);

            // Also delete any existing category/screenshot relations
            Db::delete(Table::PLUGINCATEGORIES, ['pluginId' => $this->id]);
            Db::delete(Table::PLUGINSCREENSHOTS, ['pluginId' => $this->id]);
        }

        // Save the new category/screenshot relations
        Db::batchInsert(Table::PLUGINCATEGORIES, ['pluginId', 'categoryId', 'sortOrder'], $categoryData);
        Db::batchInsert(Table::PLUGINSCREENSHOTS, ['pluginId', 'assetId', 'sortOrder'], $screenshotData);

        // Save the editions
        $elementsService = Craft::$app->getElements();
        foreach ($this->getEditions(true) as $edition) {
            $edition->pluginId = $this->id;
            $elementsService->saveElement($edition, false);
        }

        // If this is enabled, clear the plugin store caches
        if ($this->enabled) {
            touch(Module::getInstance()->getJsonDumper()->composerWebroot . '/packages.json');
        }

        $sendDevEmail = false;
        $emailSubject = null;
        $emailMessage = null;
        $published = false;

        if ($this->_submittedForApproval) {
            $this->getHistory()->push(Craft::$app->getUser()->getIdentity()->username . ' submitted the plugin for approval');
        } else if ($this->_approved) {
            $this->getHistory()->push(Craft::$app->getUser()->getIdentity()->username . ' approved the plugin', $this->devComments);
            $sendDevEmail = true;
            $emailSubject = "$this->name has been approved!";
            // Any actual licenses yet?
            $published = !empty($packageManager->getAllVersions($this->packageName, null, null, false));
            if ($published) {
                $emailMessage = <<<EOD
Congratulations, $this->name has been approved, and is now available in the Craft Plugin Store for all to enjoy.
EOD;
            } else {
                $emailMessage = <<<EOD
Congratulations, $this->name has been approved for the Craft Plugin Store!

Note that before it will show up, you’re going to need to [tag a release](https://docs.craftcms.com/v3/extend/plugin-store.html#plugin-releases) on it. 
EOD;
            }
            $emailMessage .= <<<EOD


{$this->devComments}

Thanks for submitting it!
EOD;
        } else if ($this->_changesRequested) {
            $this->getHistory()->push(Craft::$app->getUser()->getIdentity()->username . ' requested changes for the plugin', $this->devComments);
            $sendDevEmail = true;
            $emailSubject = "$this->name isn’t quite ready for prime time yet…";
            $emailMessage = <<<EOD
Thanks for submitting $this->name to the Craft Plugin Store. We couldn’t accept it quite yet, due to the following reason:

$this->devComments

Once you’ve taken care of that, re-submit your plugin and we’ll give it another look. If you have any questions, reply to this email and we’ll get back to you.
EOD;
        } else if ($this->_rejected) {
            $this->getHistory()->push(Craft::$app->getUser()->getIdentity()->username . ' rejected the plugin', $this->devComments);
            $sendDevEmail = true;
            $emailSubject = "We couldn’t accept $this->name into the Plugin Store";
            $emailMessage = <<<EOD
Thanks for submitting $this->name to the Craft Plugin Store. Unfortunately, we couldn’t accept it, due to the following reason:

$this->devComments

If you have any questions, reply to this email and we’ll get back to you.
EOD;
        } else if ($this->devComments) {
            $this->getHistory()->push(Craft::$app->getUser()->getIdentity()->username . ' sent the developer a note', $this->devComments);
            $sendDevEmail = true;
            $emailSubject = "Quick note about $this->name";
            $emailMessage = $this->devComments;
        }

        if ($sendDevEmail) {
            $emailBody = <<<EOD
Hi {$this->getDeveloper()->getFriendlyName()},

$emailMessage

–The Craft Team
EOD;

            Craft::$app->getMailer()->compose()
                ->setSubject($emailSubject)
                ->setTextBody($emailBody)
                ->setHtmlBody(Markdown::process(htmlentities($emailBody)))
                ->setTo($this->getDeveloper())
                ->send();
        }

        if ($published) {
            $this->publish();
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete(): void
    {
        Module::getInstance()->getPackageManager()->removePackage($this->packageName);
        parent::afterDelete();
    }

    /**
     * Marks the plugin as published.
     *
     * This should only be called once the plugin has any versions.
     */
    public function publish(): void
    {
        if ($this->published) {
            return;
        }

        Db::update(Table::PLUGINS, ['published' => true], ['id' => $this->id]);

        $this->published = true;

        if ($this->hasEventHandlers(self::EVENT_PUBLISHED)) {
            $this->trigger(self::EVENT_PUBLISHED);
        }
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size): ?string
    {
        if ($this->iconId) {
            return Craft::$app->getAssets()->getThumbUrl($this->getIcon(), $size, false);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        if (!$this->enabled && $this->pendingApproval) {
            return self::STATUS_PENDING;
        }

        return parent::getStatus();
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl("plugins/{$this->id}-{$this->handle}");
    }

    /**
     * Returns the replacement plugin.
     *
     * @return Plugin|null
     */
    public function getReplacement(): ?Plugin
    {
        if ($this->replacementId === null) {
            return null;
        }

        if (!isset($this->_replacement)) {
            $this->_replacement = Plugin::findOne($this->replacementId);
        }

        return $this->_replacement;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'handle':
                return "<code>{$this->handle}</code>";
            case 'packageName':
                return "<a href='http://packagist.org/packages/{$this->packageName}' target='_blank'>{$this->packageName}</a>";
            case 'repository':
            case 'documentationUrl':
                $url = $this->$attribute;
                return $url ? "<a href='{$url}' target='_blank'>" . preg_replace('/^https?:\/\/(?:www\.)?github\.com\//', '', $url) . '</a>' : '';
            case 'license':
                return $this->license === 'craft' ? 'Craft' : 'MIT';
            case 'primaryCategory':
                if ($category = ($this->getCategories()[0] ?? null)) {
                    return Craft::$app->getView()->renderTemplate('_elements/element', [
                        'element' => $category,
                    ]);
                }
                return '';
            default:
                return parent::tableAttributeHtml($attribute);
        }
    }
}
