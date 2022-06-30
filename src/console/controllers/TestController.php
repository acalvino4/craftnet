<?php

namespace craftnet\console\controllers;

use mikehaertl\shellcommand\Command as ShellCommand;
use yii\console\Controller;

class TestController extends Controller
{
    public function actionIndex()
    {
        $shellCommand = new ShellCommand();
        $shellCommand->setCommand('env');
        $success = $shellCommand->execute();

        if ($success && $shellCommand->getOutput()) {
            file_put_contents('/var/app/current/cron_env_shell_dump.txt', $shellCommand->getOutput());
        }
    }
}
