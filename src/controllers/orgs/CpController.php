<?php

namespace craftnet\controllers\orgs;

use Craft;
use craft\base\Element;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craftnet\orgs\Org;
use yii\web\Response;

class CpController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requireCpRequest();

        return parent::beforeAction($action);
    }

    public function actionCreate(): ?Response
    {
        $user = Craft::$app->getUser()->getIdentity();

        // Create & populate the draft
        $org = Craft::createObject(Org::class);
        $org->ownerId = $this->request->getQueryParam('ownerId', $user->id);

        // Title & slug
        $org->title = $this->request->getQueryParam('title');
        $org->slug = $this->request->getQueryParam('slug');
        if ($org->title && !$org->slug) {
            $org->slug = ElementHelper::generateSlug($org->title);
        }
        if (!$org->slug) {
            $org->slug = ElementHelper::tempSlug();
        }

        // Pause time so postDate will definitely be equal to dateCreated, if not explicitly defined
        DateTimeHelper::pause();

        // Save it
        $org->setScenario(Element::SCENARIO_ESSENTIALS);
        $success = Craft::$app->getDrafts()->saveElementAsDraft($org, $user->id, null, null, false);

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
