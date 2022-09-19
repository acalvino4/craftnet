<?php

namespace craftnet\plugins\conditions;

use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craftnet\Module;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginQuery;

class CmsConstraintConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return 'CMS Constraint';
    }

    public function getExclusiveQueryParams(): array
    {
        return ['cmsVersion'];
    }

    protected function operators(): array
    {
        return [self::OPERATOR_EQ];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        if (!$this->value) {
            return;
        }

        /** @var PluginQuery $query */
        $query->withLatestReleaseInfo(cmsVersion: $this->cmsVersion());
    }

    public function matchElement(ElementInterface $element): bool
    {
        if (!$this->value) {
            return true;
        }

        /** @var Plugin $element */
        return Plugin::find()
            ->withLatestReleaseInfo(cmsVersion: $this->cmsVersion())
            ->id($element->id)
            ->status(null)
            ->exists();
    }

    private function cmsVersion(): string
    {
        return Module::getInstance()->getPackageManager()->getLatestVersion('craftcms/cms', null, $this->value) ?? '0.0';
    }
}
