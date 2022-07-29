<?php

namespace craftnet\orgs;

use Craft;
use craft\base\Component;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\records\Token as TokenRecord;
use DateTime;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\UserException;
use yii\db\StaleObjectException;

class OrgsService extends Component
{
    public function createInvitation(Org $org, User $user, ?DateTime $expiryDate = null): bool
    {
        if (!$expiryDate) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->defaultTokenDuration);
            $expiryDate = DateTimeHelper::currentUTCDateTime();
            $expiryDate->add($interval);
        }

        $invitationRecord = new InvitationRecord();
        $invitationRecord->orgId = $org->id;
        $invitationRecord->userId = $user->id;

        $invitationRecord->expiryDate = Db::prepareDateForDb($expiryDate);

        return $invitationRecord->save();
    }

    /**
     * @throws StaleObjectException
     * @throws UserException
     */
    public function deleteInvitation(Org $org, User $user): bool
    {
        $invitationRecord = InvitationRecord::findOne([
            'orgId' => $org->id,
            'userId' => $user->id,
        ]);

        if (!$invitationRecord) {
            throw new UserException('Unable to find invitation.');
        }

        return (bool) $invitationRecord?->delete();
    }

    // TODO: move to org
    public function getInvitationsForOrg(Org $org): array
    {
        return InvitationRecord::find([
            'orgId' => $org->id,
        ])->all();
    }

    public function deleteExpiredInvitations(): bool
    {
        $rows = InvitationRecord::deleteAll(['<=', 'expiryDate', Db::prepareDateForDb(new DateTime())]);
        return (bool) $rows;
    }
}
