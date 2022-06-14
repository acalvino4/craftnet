<?php

namespace craftnet\behaviors;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\User;
use craft\events\DefineRulesEvent;
use craft\events\ModelEvent;
use craft\helpers\Json;
use craftnet\db\Table;
use craftnet\developers\EmailVerifier;
use craftnet\developers\FundsManager;
use craftnet\helpers\KeyHelper;
use craftnet\orgs\Org;
use craftnet\partners\Partner;
use craftnet\plugins\Plugin;
use DateTime;
use yii\base\Behavior;
use yii\base\Exception;

/**
 * The Org behavior extends users with plugin org-related features.
 *
 * @property EmailVerifier $emailVerifier
 * @property FundsManager $fundsManager
 * @property User $owner
 * @property-read string $developerName
 * @property-read \craftnet\orgs\Org[] $orgs
 * @property null|\craftnet\orgs\Org $org
 * @property-read \craftnet\partners\Partner $partner
 * @property Plugin[] $plugins
 * @mixin CustomFieldBehavior
 */
class UserBehavior extends Behavior
{
    /**
     * @var string|null
     */
    public ?string $country;

    /**
     * @var string|null
     */
    public ?string $stripeAccessToken;

    /**
     * @var string|null
     */
    public ?string $stripeAccount;

    /**
     * @var string|null
     */
    public ?string $payPalEmail;

    /**
     * @var string|null
     */
    public ?string $apiToken;

    /**
     * @var string|null
     */
    public ?string $websiteSlug;

    /**
     * @var string|null
     */
    public ?string $displayName;

    /**
     * @var string|null
     */
    public ?string $websiteUrl;

    /**
     * @var string|null
     */
    public ?string $location;

    /**
     * @var string|null
     */
    public ?string $supportPlan;

    /**
     * @var DateTime|null
     */
    public ?DateTime $supportPlanExpiryDate;

    /**
     * @var null|bool
     */
    public ?bool $enableDeveloperFeatures = false;

    /**
     * @var bool
     */
    public bool $enablePartnerFeatures = false;

    /**
     * @var Plugin[]|null
     */
    private ?array $_plugins;

    /**
     * @var Org|null
     */
    private ?Org $_org = null;

    /**
     * @var Org[]|null
     */
    private ?array $_orgs;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Element::EVENT_BEFORE_VALIDATE => [$this, 'beforeValidate'],
            Element::EVENT_DEFINE_RULES => [$this, 'defineRules'],
            Element::EVENT_BEFORE_SAVE => [$this, 'beforeSave'],
            Element::EVENT_AFTER_SAVE => [$this, 'afterSave'],
        ];
    }

    /**
     * @return Partner
     * @throws Exception if the partner element couldn't be created
     */
    public function getPartner(): Partner
    {
        /** @var Partner|null $partner */
        $partner = Partner::find()
            ->ownerId($this->owner->id)
            ->status(null)
            ->one();

        if (!$partner) {
            $partner = new Partner();
            $partner->ownerId = $this->owner->id;
            if (!Craft::$app->getElements()->saveElement($partner)) {
                throw new Exception('Couldn\'t save partner: ' . implode(', ', $partner->getErrorSummary(true)));
            }
        }

        return $partner;
    }

    /**
     * @return string
     * TODO: remove
     */
    public function getDeveloperName(): string
    {
        return $this->owner->getFieldValue('developerName') ?: $this->owner->getName();
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins(): array
    {
        if ($this->_plugins !== null) {
            return $this->_plugins;
        }

        /** @var Plugin[] $plugins */
        $plugins = Plugin::find()
            ->developerId($this->owner->id)
            ->status(null)
            ->all();
        return $this->_plugins = $plugins;
    }

    /**
     * @return EmailVerifier
     */
    public function getEmailVerifier(): EmailVerifier
    {
        return new EmailVerifier($this->owner);
    }

    /**
     * @return FundsManager
     */
    public function getFundsManager(): FundsManager
    {
        return new FundsManager($this->owner);
    }

    /**
     * Generates a new API token for the org.
     *
     * @return string the new API token
     */
    public function generateApiToken(): string
    {
        $token = KeyHelper::generateApiToken();
        $this->apiToken = Craft::$app->getSecurity()->generatePasswordHash($token, 4);
        $this->saveOrgInfo();
        return $token;
    }

    /**
     * Handles pre-validation stuff
     *
     * @return void
     */
    public function beforeValidate(): void
    {
        // Only set the PayPal email if we're saving the current user and they are an org
        if (
            (Craft::$app->getRequest()->getIsCpRequest() || $this->owner->getIsCurrent()) &&
            $this->org &&
            ($payPalEmail = Craft::$app->getRequest()->getBodyParam('payPalEmail')) !== null
        ) {
            $this->payPalEmail = $payPalEmail ?: null;
        }
    }

    /**
     * Defines validation rules for the user
     *
     * @param DefineRulesEvent $event
     * @return void
     */
    public function defineRules(DefineRulesEvent $event): void
    {
        $event->rules[] = ['payPalEmail', 'email'];
    }

    /**
     * Handles pre-user-save stuff
     */
    public function beforeSave(ModelEvent $event): void
    {
        /** @var User|self $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();
        $isAdmin = $currentUser && ($currentUser->isInGroup('admins') || $currentUser->admin);
        $request = Craft::$app->getRequest();

        if (
            $currentUser &&
            !$isAdmin &&
            $request->getIsSiteRequest() &&
            $request->getIsPost()
        ) {
            $postedEnableShowcaseFeatures = $request->getBodyParam('fields.enableShowcaseFeatures');
            $postedEnablePartnerFeatures = $request->getBodyParam('fields.enablePartnerFeatures');

            if (
                ($postedEnableShowcaseFeatures !== null && $postedEnableShowcaseFeatures != $currentUser->enableShowcaseFeatures) ||
                ($postedEnablePartnerFeatures !== null && $postedEnablePartnerFeatures != $currentUser->enablePartnerFeatures)
            ) {
                Craft::warning("There was a validation error while saving the user's partner status", __METHOD__);
                $event->sender->addError('enablePartnerFeatures', "There was a problem saving your partner status.");
                $event->isValid = false;
            }

        }
    }

    /**
     * Handles post-user-save stuff
     * Note: `enablePluginDeveloperFeatures` is not actually a field.
     * TODO: do we even need to worry about the developer group any longer?
     */
    public function afterSave(): void
    {
        $request = Craft::$app->getRequest();
        $currentUser = Craft::$app->getUser()->getIdentity();

        // If it's a front-end site POST request and they're not currently a developer, check to see if they've opted into developer features.
        if (
            $currentUser &&
            $currentUser->id == $this->owner->id &&
            $request->getIsSiteRequest() &&
            $request->getIsPost() &&
            $request->getBodyParam('fields.enablePluginDeveloperFeatures') &&
            $this->org &&
            !$this->enableDeveloperFeatures
        ) {
            // Get any existing group IDs.
            $userGroupsService = Craft::$app->getUserGroups();
            $existingGroups = $userGroupsService->getGroupsByUserId($currentUser->id);
            $groupIds = [];

            foreach ($existingGroups as $existingGroup) {
                $groupIds[] = $existingGroup->id;
            }

            // Add the developer group.
            $groupIds[] = $userGroupsService->getGroupByHandle('developers')->id;

            Craft::$app->getUsers()->assignUserToGroups($currentUser->id, $groupIds);
        }

        if ($this->org) {
            $this->saveOrgInfo();
        }
    }

    /**
     * Updates the org data.
     */
    public function saveOrgInfo(): void
    {
        Craft::$app->getDb()->createCommand()
            ->upsert(Table::ORGS, [
                'id' => $this->owner->id,
            ], [
                'country' => $this->country,
                'stripeAccessToken' => $this->stripeAccessToken,
                'stripeAccount' => $this->stripeAccount,
                'payPalEmail' => $this->payPalEmail,
                'apiToken' => $this->apiToken,
                'websiteSlug' => $this->websiteSlug,
                'displayName' => $this->displayName,
                'websiteUrl' => $this->websiteUrl,
                'location' => $this->location,
                'supportPlan' => $this->supportPlan,
                'supportPlanExpiryDate' => $this->supportPlanExpiryDate,
                'enableDeveloperFeatures' => $this->enableDeveloperFeatures,
                'enablePartnerFeatures' => $this->enablePartnerFeatures,
            ], [], false)
            ->execute();
    }

    /**
     * @return Org|null
     */
    public function getOrg(): ?Org
    {
        if ($this->_org !== null) {
            return $this->_org;
        }

        $isOrg = (new Query())
            ->from(Table::ORGS)
            ->where(['id' => $this->owner->id])
            ->exists();

        if (!$isOrg) {
            return null;
        }

        return ($this->_org = new Org($this->owner));
    }

    public function getOrgs(): array
    {
        if ($this->_orgs !== null) {
            return $this->_orgs;
        }

        return $this->_orgs = Craft::$app->getOrgs()->getOrgsByMemberUserId($this->owner->id);
    }

    /**
     * @param Org|null $org
     * @return void
     */
    public function setOrg(?Org $org): void
    {
        $this->_org = $org;
    }
}
