<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;

/**
 * m220812_161427_fix_plugin_issue_stat_fks migration.
 */
class m220812_161427_fix_plugin_issue_stat_fks extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropForeignKeyIfExists('craftnet_plugin_issue_stats', ['pluginId']);
        $this->addForeignKey(null, 'craftnet_plugin_issue_stats', ['pluginId'], 'craftnet_plugins', ['id'], 'CASCADE');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220812_161427_fix_plugin_issue_stat_fks cannot be reverted.\n";
        return false;
    }
}
