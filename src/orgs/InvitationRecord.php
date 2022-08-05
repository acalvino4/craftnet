<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftnet\orgs;

use craft\db\ActiveRecord;
use craft\validators\DateTimeValidator;
use craftnet\db\Table;
use DateTime;

/**
 * @property int $id
 * @property int $orgId
 * @property int $userId
 * @property string $isAdmin
 * @property DateTime $expiryDate
 */
class InvitationRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ORGS_INVITATIONS;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['expiryDate'], DateTimeValidator::class],
            [['orgId', 'userId', 'expiryDate', 'isAdmin'], 'required'],
        ];
    }
}
