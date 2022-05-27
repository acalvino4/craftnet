<?php

namespace craftnet\composer\jobs;

use craft\helpers\FileHelper;
use craft\queue\BaseJob;

class DeletePaths extends BaseJob
{
    /**
     * @var string[]
     */
    public $paths;

    public function execute($queue): void
    {
        foreach ($this->paths as $path) {
            if (file_exists($path)) {
                FileHelper::unlink($path);
            }
        }
    }
}
