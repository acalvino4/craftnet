<?php

namespace craftnet\plugins\conditions;

use craft\base\conditions\BaseSelectConditionRule;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craftnet\behaviors\UserBehavior;
use craftnet\behaviors\UserQueryBehavior;
use craftnet\db\Table;
use craftnet\plugins\Plugin;
use craftnet\plugins\PluginQuery;
use yii\db\Expression;

class DeveloperConditionRule extends BaseSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return 'Developer';
    }

    public function getExclusiveQueryParams(): array
    {
        return ['developerId'];
    }

    protected function options(): array
    {
        return User::find()
            ->where([
                'exists',
                (new Query())
                    ->from(Table::PLUGINS)
                    ->where(['developerId' => new Expression('[[elements.id]]')])
            ])
            ->collect()
            ->map(fn(User|UserBehavior $user) => [
                'label' => $user->getDeveloperName(),
                'value' => $user->id
            ])
            ->values()
            ->all();
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        if (!$this->value) {
            return;
        }

        /** @var PluginQuery $query */
        $query->developerId($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Plugin $element */
        return $this->matchValue($element->developerId);
    }
}
