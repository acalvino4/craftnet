<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\MatrixBlock;
use craft\errors\UnsupportedSiteException;
use craftnet\orgs\Org;
use Throwable;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class OrgsController extends SiteController
{
    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionGetOrg(int $orgId): Response
    {
        $org = Org::find()->id($orgId)->one();

        if (!$org) {
        throw new NotFoundHttpException();
        }

        if (!$org->canView($this->currentUser)) {
            throw new ForbiddenHttpException();
        }

        $projects = $org->partnerProjects
            ->collect()
            ->map(fn(MatrixBlock $block) => $block->getAttributes([
                'projectName',
                'projectUrl',
                'role',
                'withCraftCommerce',
                'linkType',
            ]) + [
                'partnerProjects' => $block->screenshots->collect()
                    ->map(fn(Asset $asset) => $asset->getAttributes(['id', 'url'])),
            ]);

        $transformed = static::transformOrg($org) + [
            'projects' => $projects->all(),
        ] + $org->getAttributes([
            'primaryContactName',
            'partnerRegion',
            'location',
            'externalUrl',
            'payPalEmail',
            'enablePartnerFeatures',
            'primaryContactName',
            'primaryContactEmail',
            'primaryContactPhone',
            'partnerFullBio',
            'partnerShortBio',
            'partnerHasFullTimeDev',
            'partnerIsCraftVerified',
            'partnerIsCommerceVerified',
            'partnerIsEnterpriseVerified',
            'partnerIsRegisteredBusiness',
            'partnerAgencySize',
            'partnerCapabilities',
            'partnerExpertise',
            'partnerVerificationStartDate',
            'partnerRegion',
        ]);

        return $this->asSuccess(data: $transformed);
    }

    public function actionGetOrgs(): Response
    {
        $orgs = Org::find()->hasMember($this->currentUser)->collect()
            ->map(fn($org) => static::transformOrg($org));

        return $this->asSuccess(data: $orgs->all());
    }

    /**
     * @throws Throwable
     * @throws ForbiddenHttpException
     */
    public function actionSaveOrg(?int $orgId = null): Response
    {
        $this->requirePostRequest();
        $isNew = !$orgId;
        $siteId = $this->request->getBodyParam('siteId');

        if ($isNew) {
            $element = new Org();
            $element->setOwner($this->currentUser);
            $element->creatorId = $this->currentUser->id;
            if ($siteId) {
                $element->siteId = $siteId;
            }
        } else {
            $element = Org::find()
                ->status(null)
                ->siteId($siteId)
                ->id($orgId)
                ->one();

            if (!$element) {
                throw new NotFoundHttpException('Organization not found.');
            }
        }

        if (!$element->canSave($this->currentUser)) {
            throw new ForbiddenHttpException('User not authorized to save this organization.');
        }

        // Native element attributes
        $element->slug = $this->request->getBodyParam('slug', $element->slug);
        $element->title = $this->request->getBodyParam('title', $element->title);
        $element->enabled = $this->request->getBodyParam('enabled', $element->enabled);

        // Org attributes
        $element->paymentMethodId = $this->request->getBodyParam('paymentMethodId', $element->paymentMethodId) ?: null;
        $element->locationAddressId = $this->request->getBodyParam('locationAddressId', $element->locationAddressId) ?: null;

        $element->setFieldValuesFromRequest('fields');

        if (!$element->enabled && !Craft::$app->getUser()->getHasElevatedSession()) {
            return $this->getElevatedSessionResponse();
        }

        if ($element->enabled && $element->getEnabledForSite()) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        try {
            $success = Craft::$app->getElements()->saveElement($element);
        } catch (UnsupportedSiteException $e) {
            $element->addError('siteId', $e->getMessage());
            $success = false;
        }

        if (!$success) {
            return $this->asModelFailure(
                $element,
                'Couldn’t save organization.',
                'org',
            );
        }

        return $this->asModelSuccess(
            $element,
            $isNew ? 'Organization created.' : 'Organization saved.',
        );
    }
}
