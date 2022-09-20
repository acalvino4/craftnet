<?php

namespace craftnet\plugins\conditions;

use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginQuery;

class HandleConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return 'Handle';
    }

    public function getExclusiveQueryParams(): array
    {
        return ['handle'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var PluginQuery $query */
        $query->handle($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Plugin $element */
        return $this->matchValue($element->handle);
    }
}
