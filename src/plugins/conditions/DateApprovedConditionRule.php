<?php

namespace craftnet\plugins\conditions;

use craft\base\conditions\BaseDateRangeConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginQuery;

class DateApprovedConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return 'Date Approved';
    }

    public function getExclusiveQueryParams(): array
    {
        return ['dateApproved'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var PluginQuery $query */
        $query->dateApproved($this->queryParamValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Plugin $element */
        return $this->matchValue($element->dateApproved);
    }
}
