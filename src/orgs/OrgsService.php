<?php

namespace craftnet\orgs;

use craft\base\Component;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craftnet\db\Table;
use Illuminate\Support\Collection;

class OrgsService extends Component
{
    /**
     * Gets orgs by a member's user ID.
     *
     * @param int $userId
     * @return Collection
     */
    public function getOrgsByMemberUserId(int $userId): Collection
    {
        return User::find()
            ->leftJoin(['orgs_members' => Table::ORGS_MEMBERS], '[[orgs_members.orgId]] = [[users.id]]')
            ->where(['orgs_members.userId' => $userId])
            ->collect();
    }
}
