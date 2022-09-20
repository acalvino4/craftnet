<?php

namespace craftnet\plugins\conditions;

use craft\base\conditions\BaseSelectConditionRule;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginQuery;

class LicenseConditionRule extends BaseSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return 'License';
    }

    public function getExclusiveQueryParams(): array
    {
        return ['license'];
    }

    protected function options(): array
    {
        return [
            'craft' => 'Craft',
            'mit' => 'MIT',
        ];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        if (!$this->value) {
            return;
        }

        /** @var PluginQuery $query */
        $query->license($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Plugin $element */
        return $this->matchValue($element->license);
    }
}
