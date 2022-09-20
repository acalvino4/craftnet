<?php

namespace craftnet\plugins\conditions;

use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginQuery;

class PackageNameConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return 'Package Name';
    }

    public function getExclusiveQueryParams(): array
    {
        return ['packageName', 'packageId'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var PluginQuery $query */
        $query->packageName($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Plugin $element */
        return $this->matchValue($element->packageName);
    }
}
