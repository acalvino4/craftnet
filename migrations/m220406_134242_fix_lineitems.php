<?php

namespace craft\contentmigrations;

use Craft;
use craft\commerce\db\Table;
use craft\db\Migration;

/**
 * m220606_134242_fix_lineitems migration.
 */
class m220406_134242_fix_lineitems extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $nullableCols = ['description', 'sku'];

        foreach ($nullableCols as $col) {
            $this->update(Table::LINEITEMS, [
                $col => ''
            ], [
                $col => null
            ]);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220406_134242_fix_lineitems cannot be reverted.\n";
        return false;
    }
}
