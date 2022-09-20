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
            $query->cmsVersion($this->cmsVersion());
        } else {
            $compatiblePluginIds = (clone $query)
                ->cmsVersion($this->cmsVersion())
                ->limit(null)
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
            ->cmsVersion($this->cmsVersion())
            ->id($element->id)
            ->status(null)
            ->exists();

        if ($this->operator === self::OPERATOR_EQ) {
            return $compatible;
        }

        return !$compatible;
    }

    /**
     * @return string|string[]
     */
    private function cmsVersion(): string|array
    {
        $constraints = array_filter(array_map('trim', explode('|', $this->value)));
        $cmsVersions = [];
        $packageManager = Module::getInstance()->getPackageManager();
        foreach ($constraints as $constraint) {
            $cmsVersion = $packageManager->getLatestVersion('craftcms/cms', null, $constraint);
            if ($cmsVersion) {
                $cmsVersions[] = $cmsVersion;
            }
        }
        if (empty($cmsVersions)) {
            return '0.0';
        }
        return count($cmsVersions) === 1 ? $cmsVersions[0] : $cmsVersions;
    }
}
