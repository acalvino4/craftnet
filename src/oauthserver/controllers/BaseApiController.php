<?php

namespace craftnet\oauthserver\controllers;

use craft\web\Controller;

/**
 * Class BaseController
 */
abstract class BaseApiController extends Controller
{
    /**
     * @inheritdoc
     */
    public array|int|bool $allowAnonymous = true;

    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;
}
