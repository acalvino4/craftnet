<?php

namespace craftnet\controllers\console;

class UsersController extends BaseController
{
    public function actionSaveUser(int $userId)
    {
        return $this->run('/users/save-user');
    }

    public function actionUploadUserPhoto(int $userId)
    {
        return $this->run('/users/upload-user-photo');
    }
}
