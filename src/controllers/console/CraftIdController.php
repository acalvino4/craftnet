<?php

namespace craftnet\controllers\console;

use Craft;
use yii\web\Response;

/**
 * Class CraftIdController
 */
class CraftIdController extends BaseController
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * @return Response
     */
    public function actionCountries(): Response
    {
        $countries = Craft::$app->getApi()->getCountries();

        return $this->asJson($countries);
    }
}
