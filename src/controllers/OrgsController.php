<?php

namespace craftnet\controllers;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\web\Controller;
use craft\web\UrlManager;
use craftnet\orgs\Org;
use craftnet\partners\Partner;
use craftnet\partners\PartnerHistory;
use craftnet\partners\PartnerService;
use GuzzleHttp\Exception\RequestException;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class OrgsController extends Controller
{
    public function actionCreate(): ?Response
    {
        $user = Craft::$app->getUser()->getIdentity();

        // Create & populate the draft
        $org = Craft::createObject(Org::class);

        // Title & slug
        $org->title = $this->request->getQueryParam('title');
        $org->slug = $this->request->getQueryParam('slug');
        if ($org->title && !$org->slug) {
            $org->slug = ElementHelper::generateSlug($org->title, null);
        }
        if (!$org->slug) {
            $org->slug = ElementHelper::tempSlug();
        }

        // Pause time so postDate will definitely be equal to dateCreated, if not explicitly defined
        DateTimeHelper::pause();

        // Save it
        $org->setScenario(Element::SCENARIO_ESSENTIALS);
        $success = Craft::$app->getDrafts()->saveElementAsDraft($org, $user->getId(), null, null, false);

        // Resume time
        DateTimeHelper::resume();

        if (!$success) {
            return $this->asModelFailure(
                $org,
                Craft::t('app', 'Couldn’t create organization.'),
                'org',
            );
        }

        $editUrl = $org->getCpEditUrl();

        $response = $this->asModelSuccess(
            $org,
            Craft::t('app', 'Organization created.'),
            'entry',
            array_filter([
                'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
            ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }
}
