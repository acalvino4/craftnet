<?php

namespace craftnet\behaviors;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\events\DefineRulesEvent;
use craft\events\ModelEvent;
use craftnet\db\Table;
use craftnet\developers\EmailVerifier;
use craftnet\developers\FundsManager;
use craftnet\helpers\KeyHelper;
use craftnet\Module;
use craftnet\orgs\Org;
use craftnet\partners\Partner;
use craftnet\plugins\Plugin;
use DateTime;
use Illuminate\Support\Collection;
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
 * @property-read \craftnet\partners\Partner $partner
 * @property Plugin[] $plugins
= * @mixin CustomFieldBehavior
 */
class UserBehavior extends Behavior
{
    /**
     * @var string|null
     */
    public ?string $country = null;

    /**
     * @var string|null
     */
    public ?string $stripeAccessToken = null;

    /**
     * @var string|null
     */
    public ?string $stripeAccount = null;

    /**
     * @var string|null
     */
    public ?string $payPalEmail = null;

    /**
     * @var string|null
     */
    public ?string $apiToken = null;

    /**
     * @var string|null
     */
    public ?string $websiteSlug = null;

    /**
     * @var string|null
     */
    public ?string $displayName = null;

    /**
     * @var string|null
     */
    public ?string $websiteUrl = null;

    /**
     * @var string|null
     */
    public ?string $location = null;

    /**
     * @var string|null
     */
    public ?string $supportPlan = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $supportPlanExpiryDate = null;

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
    private ?array $_plugins = null;

    /**
     * @var bool
     */
    public bool $isOrg = false;

    /**
     * @var Collection|null
     */
    private ?Collection $_orgs = null;

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
            $this->isOrg &&
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
            $this->isOrg &&
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

        if ($this->isOrg) {
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
     * @throws Exception
     */
    public function addOrgAdmin(User $user): bool
    {
        $this->_requireOrg();

        return (bool)Craft::$app->getDb()->createCommand()
            ->upsert(Table::ORGS_MEMBERS, [
                'orgId' => $this->owner->id,
                'userId' => $user->owner->id,
                'admin' => true,
            ])
            ->execute();
    }

    public function findOrgs(): UserQuery
    {
        return User::find()
            ->innerJoin(['orgs_members' => Table::ORGS_MEMBERS], '[[orgs_members.orgId]] = [[users.id]]')
            ->andWhere(['orgs_members.userId' => $this->owner->id]);
    }

    /**
     * @throws Exception
     */
    public function findOrgMembers(): UserQuery
    {
        $this->_requireOrg();

        return User::find()
            ->innerJoin(['orgs_members' => Table::ORGS_MEMBERS], '[[orgs_members.userId]] = [[users.id]]')
            ->andWhere(['orgs_members.orgId' => $this->owner->id]);
    }

    /**
     * @throws Exception
     */
    public function findOrgAdmins(): UserQuery
    {
        $this->_requireOrg();

        return $this->findOrgMembers()->andWhere(['orgs_members.admin' => true]);
    }

    private function _requireOrg(): void
    {
        if (!$this->isOrg) {
            throw new Exception('User is not an organization.');
        }
    }
}
