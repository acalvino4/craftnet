<?php

namespace craftnet\plugins\conditions;

use craft\elements\conditions\ElementCondition;

class PluginCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            CmsConstraintConditionRule::class,
            DeveloperConditionRule::class,
        ]);
    }
}
