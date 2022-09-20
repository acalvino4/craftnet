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
            $compatiblePluginIds = (clone $query)
                ->withLatestReleaseInfo(cmsVersion: $this->cmsVersion())
                ->ids();
            if ($compatiblePluginIds) {
                $query->id(['not'] + $compatiblePluginIds);
            }
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        if (!$this->value) {
            return true;
        }

        $compatible = Plugin::find()
            ->withLatestReleaseInfo(cmsVersion: $this->cmsVersion())
            ->id($element->id)
            ->status(null)
            ->exists();

        if ($this->operator === self::OPERATOR_EQ) {
            return $compatible;
        }

        return !$compatible;
    }

    private function cmsVersion(): string
    {
        return Module::getInstance()->getPackageManager()->getLatestVersion('craftcms/cms', null, $this->value) ?? '0.0';
    }
}
