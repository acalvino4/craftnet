<?php

namespace craftnet\controllers\api\v1;

use craft\helpers\App;
use craftnet\controllers\api\BaseApiController;
use craftnet\partners\Partner;
use craftnet\partners\PartnerService;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PartnersController extends BaseApiController
{
    public function init(): void
    {
        parent::init();
        $secret = $this->request->getHeaders()->get('X-Partner-Secret');
        if ($secret !== App::env('PARTNER_SECRET')) {
            throw new BadRequestHttpException('Wrong secret');
        }
    }

    public function actionList(): Response
    {
        $ids = Partner::find()
            ->ids();
        return $this->asJson(['ids' => $ids]);
    }

    public function actionGet(int $id): Response
    {
        $partner = Partner::find()
            ->id($id)
            ->one();

        if (!$partner) {
            throw new NotFoundHttpException('No partner exists with an ID of ' . $id);
        }

        $data = PartnerService::getInstance()->serializePartner($partner);
        return $this->asJson($data);
    }
}
