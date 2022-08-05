<?php

namespace craftnet\behaviors;

use craft\elements\db\ElementQuery;
use craft\elements\db\UserQuery;
use craftnet\db\Table;
use craftnet\orgs\Org;
use craftnet\orgs\OrgQuery;
use Illuminate\Support\Collection;
use yii\base\Behavior;
use yii\base\InvalidArgumentException;
use yii\db\Connection;

/**
 * @property UserQuery $owner
 */
class UserQueryBehavior extends Behavior
{
    public ?bool $orgOwner = null;
    public ?bool $orgAdmin = null;
    public ?bool $orgMember = null;
    private null|int|array $ofOrg = null;

    public function ofOrg(mixed $value): UserQuery|static
    {
        $this->orgMember = (bool) $value;
        $this->ofOrg = static::normalizeOrgOfArgument($value);
        return $this->owner;
    }

    public function orgAdmin(?bool $value): UserQuery|static
    {
        $this->orgMember = $value;
        $this->orgAdmin = $value;
        return $this->owner;
    }

    public function orgOwner(?bool $value): UserQuery|static
    {
        $this->orgMember = $value;
        $this->orgOwner = $value;
        return $this->owner;
    }

    public function orgMember(?bool $value): UserQuery|static
    {
        $this->orgMember = $value;
        return $this->owner;
    }

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            ElementQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
        ];
    }

    public function beforePrepare(): void
    {
        $this->beforePrepareLegacy();

        if ($this->orgMember === null) {
            return;
        }

        // TODO: this will break when calling ids()
        $this->owner->query->distinct();

        $this->owner->subQuery->leftJoin(['orgsMembers' => Table::ORGS_MEMBERS], '[[orgsMembers.userId]] = [[users.id]]');
        $this->owner->subQuery->andWhere($this->orgMember ? ['not', ['orgsMembers.orgId' => null]] : ['orgsMembers.orgId' => null]);

        if ($this->orgOwner !== null) {
            $this->owner->subQuery->innerJoin(['orgs' => Table::ORGS], '[[orgs.ownerId]] = [[orgsMembers.userId]]');
        }

        if ($this->ofOrg) {
            $this->owner->subQuery->andWhere([
                ($this->orgOwner ? 'orgs.id' : 'orgsMembers.orgId') => $this->ofOrg,
            ]);
        }

        if ($this->orgAdmin !== null) {
            $this->owner->subQuery->andWhere(['orgsMembers.admin' => $this->orgAdmin]);
        }
    }

    /**
     * TODO: remove this following org migration.
     * @return void
     */
    public function beforePrepareLegacy(): void
    {
        if ($this->owner->select === ['COUNT(*)']) {
            return;
        }

        $this->owner->query->addSelect([
            'developers.country',
            'developers.stripeAccessToken',
            'developers.stripeAccount',
            'developers.payPalEmail',
            'developers.apiToken',
            'developers.balance',
        ]);

        $this->owner->query->leftJoin(['developers' => Table::DEVELOPERS], '[[developers.id]] = [[users.id]]');
        $this->owner->subQuery->leftJoin(['developers' => Table::DEVELOPERS], '[[developers.id]] = [[users.id]]');
    }

    private static function normalizeOrgOfArgument(mixed $value, bool $recursive = true): null|bool|int|array
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof Org && $value->id) {
            return $value->id;
        }

        if ($value instanceof OrgQuery) {
            return $value->ids();
        }

        if ($recursive && is_array($value)) {
            return Collection::make($value)
                ->map(fn($org) => static::normalizeOrgOfArgument($org, false))
                ->all();
        }

        throw new InvalidArgumentException('Invalid orgOf value');
    }
}
