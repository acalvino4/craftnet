<?php

namespace craftnet\cli\controllers;

use craftnet\Module;
use yii\console\Controller;

/**
 * Regenerates Composer repository JSON files.
 *
 * @property Module $module
 */
class DumpJsonController extends Controller
{
    /**
     * @var bool Whether the action should be added to the queue
     */
    public $queue = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'queue';
        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        $aliases = parent::optionAliases();
        $aliases['q'] = 'queue';
        return $aliases;
    }

    /**
     * Regenerates Composer repository JSON files.
     */
    public function actionIndex()
    {
        $this->module->getJsonDumper()->dump($this->queue);
    }
}
