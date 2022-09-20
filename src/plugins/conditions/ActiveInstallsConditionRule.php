<?php

namespace craftnet\plugins\conditions;

use craft\base\conditions\BaseNumberConditionRule;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginQuery;

class ActiveInstallsConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return 'Active Installs';
    }

    public function getExclusiveQueryParams(): array
    {
        return ['activeInstalls'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        if (!$this->value) {
            return;
        }

        /** @var PluginQuery $query */
        $query->activeInstalls($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Plugin $element */
        return $this->matchValue($element->activeInstalls);
    }
}
