<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\base\Element;
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
    public function actionGetOrg($id): Response
    {
        /** @var Org $org */
        $org = Org::find()->id($id)->one();

        if (!$org) {
            throw new NotFoundHttpException();
        }

        if (!$org->canView($this->_currentUser)) {
            throw new ForbiddenHttpException();
        }

        return $this->asSuccess(data: static::_transformOrg($org));
    }

    /**
     * Get all orgs current user is a member of
     *
     * @return Response
     */
    public function actionGetOrgs(): Response
    {
        $orgs = Org::find()->hasMember($this->_currentUser)->collect()
            ->map(fn($org) => static::_transformOrg($org));

        return $this->asSuccess(data: $orgs->all());
    }

    /**
     * @throws Throwable
     * @throws ForbiddenHttpException
     */
    public function actionSaveOrg(): Response
    {
        $this->requirePostRequest();
        $elementId = $this->request->getBodyParam('orgId');
        $siteId = $this->request->getBodyParam('siteId');
        $isNew = !$elementId;

        if ($isNew) {
            $element = new Org();
            if ($siteId) {
                $element->siteId = $siteId;
            }
        } else {
            $element = Org::find()
                ->status(null)
                ->siteId($siteId)
                ->id($elementId)
                ->one();

            if (!$element) {
                throw new NotFoundHttpException('Organization not found');
            }
        }

        if (!$element->canSave($this->_currentUser)) {
            throw new ForbiddenHttpException('User not authorized to save this organization.');
        }

        $element->slug = $this->request->getBodyParam('slug', $element->slug);
        $element->title = $this->request->getBodyParam('title', $element->title);
        $element->setFieldValuesFromRequest('fields');

        if ($isNew) {
            $element->creatorId = $this->_currentUser->id;
        }

        if ($element->enabled && $element->getEnabledForSite()) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        // TODO: do we need mutex?

        try {
            $success = Craft::$app->getElements()->saveElement($element);
        } catch (UnsupportedSiteException $e) {
            $element->addError('siteId', $e->getMessage());
            $success = false;
        }

        if (!$success) {
            return $this->asModelFailure(
                $element,
                Craft::t('app', 'Couldn’t save organization.'),
                'org'
            );
        }

        if ($isNew) {
            $element->addOwner($this->_currentUser);
        }

        return $this->asModelSuccess(
            $element,
            Craft::t('app', 'Organization saved.'),
        );
    }
}
