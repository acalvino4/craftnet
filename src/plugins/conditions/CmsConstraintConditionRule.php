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
        return [
            self::OPERATOR_EQ,
            self::OPERATOR_NE,
        ];
    }

    protected function operatorLabel(string $operator): string
    {
        return match ($operator) {
            self::OPERATOR_EQ => 'compatible with',
            self::OPERATOR_NE => 'not compatible with',
        };
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        if (!$this->value) {
            return;
        }

        /** @var PluginQuery $query */
        if ($this->operator === self::OPERATOR_EQ) {
            $query->withLatestReleaseInfo(cmsVersion: $this->cmsVersion());
        } else {
            $query->withLatestReleaseInfo(excludeCmsVersion: $this->cmsVersion());
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        if (!$this->value) {
            return true;
        }

        $query = Plugin::find();

        if ($this->operator === self::OPERATOR_EQ) {
            $query->withLatestReleaseInfo(cmsVersion: $this->cmsVersion());
        } else {
            $query->withLatestReleaseInfo(excludeCmsVersion: $this->cmsVersion());
        }

        /** @var Plugin $element */
        return $query
            ->id($element->id)
            ->status(null)
            ->exists();
    }

    private function cmsVersion(): string
    {
        return Module::getInstance()->getPackageManager()->getLatestVersion('craftcms/cms', null, $this->value) ?? '0.0';
    }
}
