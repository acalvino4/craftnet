<?php

namespace craftnet\orgs;

use Craft;
use craft\db\Query;
use craft\elements\User;
use craftnet\behaviors\UserBehavior;
use craftnet\db\Table;
use yii\base\BaseObject;

class Org extends BaseObject
{
    /**
     * @var User|UserBehavior
     */
    public $user;

    /**
     * @inheritdoc
     */
    public function __construct(User $user, array $config = [])
    {
        $this->user = $user;
        parent::__construct($config);
    }

    /**
     * @throws \yii\db\Exception
     */
    public function addAdmin(User $user): bool
    {
        return (bool) Craft::$app->getDb()->createCommand()
            ->upsert(Table::ORGS_MEMBERS, [
                'orgId' => $this->user->id,
                'userId' => $user->id,
                'admin' => true,
            ])
            ->execute();
    }

    public function getMemberIds(): array
    {
        return $this->_createMemberIdsQuery()->column();
    }

    public function getAdminIds(): array
    {
        return $this->_createMemberIdsQuery()
            ->andWhere(['admin' => true])
            ->column();
    }

    private function _createMemberIdsQuery(): Query
    {
        return (new Query())
            ->select(['userId'])
            ->from(Table::ORGS_MEMBERS)
            ->where(['orgId' => $this->user->id]);
    }
}
