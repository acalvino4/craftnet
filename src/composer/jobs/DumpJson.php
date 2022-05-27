<?php

namespace craftnet\composer\jobs;

use Craft;
use craft\queue\BaseJob;
use craftnet\Module;

class DumpJson extends BaseJob
{
    public function execute($queue): void
    {
        Craft::info('Executing DumpJson job.', __METHOD__);
        Module::getInstance()->getJsonDumper()->dump();
    }

    protected function defaultDescription(): ?string
    {
        return 'Dump Composer repo JSON';
    }
}
