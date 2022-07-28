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
    /**
     * @throws Exception
     */
    public function createInvitation(Org $org, User $user, ?DateTime $expiryDate = null, ?string $token = null): string|false
    {
        if ($token !== null && strlen($token) !== 32) {
            throw new InvalidArgumentException("Invalid token: $token");
        }

        if (!$expiryDate) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->defaultTokenDuration);
            $expiryDate = DateTimeHelper::currentUTCDateTime();
            $expiryDate->add($interval);
        }

        $invitationRecord = new InvitationRecord();
        $invitationRecord->orgId = $org->id;
        $invitationRecord->userId = $user->id;

        // TODO: I don't think we actually need a token at all…
        $invitationRecord->token = $token ?? Craft::$app->getSecurity()->generateRandomString();

        $invitationRecord->expiryDate = Db::prepareDateForDb($expiryDate);
        $success = $invitationRecord->save();

        if ($success) {
            return $invitationRecord->token;
        }

        return false;
    }

    /**
     * @throws StaleObjectException
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
