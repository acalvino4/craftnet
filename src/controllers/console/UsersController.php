<?php

namespace craftnet\controllers\console;

class UsersController extends \craft\controllers\UsersController
{
    public function bindActionParams($action, $params): array
    {
        return parent::bindActionParams(
            $action,
            BaseController::injectUserIdParam($params)
        );
    }
}
