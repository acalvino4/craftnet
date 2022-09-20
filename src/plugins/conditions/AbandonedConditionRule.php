<?php

namespace craftnet\plugins\conditions;

use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginQuery;

class AbandonedConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return 'Abandoned';
    }

    public function getExclusiveQueryParams(): array
    {
        return ['abandoned'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var PluginQuery $query */
        $query->abandoned($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Plugin $element */
        return $this->matchValue($element->abandoned);
    }
}
