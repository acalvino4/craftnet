<?php

namespace craftnet\controllers\orgs;

use craftnet\controllers\console\BaseController;

class SiteController extends BaseController
{
    public function beforeAction($action): bool
    {
        $this->requireAcceptsJson();
        return parent::beforeAction($action);
    }
}
