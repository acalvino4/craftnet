<?php

namespace craftnet\orgs;

use craft\base\Component;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craftnet\db\Table;

class OrgsService extends Component
{
    /**
     * Gets orgs by a user ID.
     *
     * @param int $userId
     * @return User[]
     */
    public function getOrgsByMemberUserId(int $userId): array
    {
        return User::find()
            ->leftJoin(['orgs_members' => Table::ORGS_MEMBERS], '[[orgs_members.orgId]] = [[users.id]]')
            ->where(['orgs_members.userId' => $userId])
            ->all();
    }
}