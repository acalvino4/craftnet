<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftnet\orgs;

use craft\db\ActiveRecord;
use craftnet\db\Table;

/**
 * @property int $id
 * @property int $orgId
 * @property int $userId
 * @property string $admin
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
            [['orgId', 'userId', 'admin'], 'required'],
        ];
    }
}
