<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\db\Table as CraftTable;
use craftnet\db\Table;

/**
 * m220526_183917_add_orgs migration.
 */
class m220526_183917_add_orgs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable(TABLE::ORGS, [
            'id' => $this->primaryKey(),
            'creatorId' => $this->integer(),
            'balance' => $this->decimal(14, 4)->notNull()->defaultValue(0),
            'stripeAccessToken' => $this->text()->null(),
            'stripeAccount' => $this->string()->null(),
            'apiToken' => $this->char(60)->null(),
            'requireOrderApproval' => $this->boolean()->defaultValue(true),
            'billingAddressId' => $this->integer(),
            'paymentSourceId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::ORGS, ['id'], CraftTable::ELEMENTS, ['id'], 'CASCADE');
        $this->addForeignKey(null, Table::ORGS, ['creatorId'], CraftTable::USERS, ['id']);

        $this->createTable(Table::ORGS_MEMBERS, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'orgId' => $this->integer()->notNull(),
            'owner' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::ORGS_MEMBERS, ['userId'], CraftTable::USERS, ['id'], 'CASCADE');
        $this->addForeignKey(null, Table::ORGS_MEMBERS, ['orgId'], Table::ORGS, ['id'], 'CASCADE');
        $this->createIndex(null, Table::ORGS_MEMBERS, ['userId', 'orgId'], true);

        $this->createTable(Table::ORGS_ORDERS, [
            'id' => $this->primaryKey(),
            'orgId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::ORGS_ORDERS, ['id'], \craft\commerce\db\Table::ORDERS, ['id'], 'CASCADE');
        $this->addForeignKey(null, Table::ORGS_ORDERS, ['orgId'], Table::ORGS, ['id'], 'CASCADE');

        $this->dropForeignKey('craftcom_plugins_developerId_fk', Table::PLUGINS);
        $this->addForeignKey('craftcom_plugins_developerId_fk', Table::PLUGINS, ['developerId'], CraftTable::ELEMENTS, ['id'], 'CASCADE');

        $this->dropForeignKey('craftnet_pluginlicenses_ownerId_fk', Table::PLUGINLICENSES);
        $this->addForeignKey('craftnet_pluginlicenses_ownerId_fk', Table::PLUGINLICENSES, ['ownerId'], CraftTable::ELEMENTS, ['id']);

        $this->dropForeignKey('fk_nadpvidlmfiafsxiwgkwdoerxoglbldsiuoz', Table::PAYOUT_ITEMS);
        $this->addForeignKey('craftnet_payout_items_developerId_fk', Table::PAYOUT_ITEMS, ['developerId'], CraftTable::ELEMENTS, ['id']);

        $this->dropForeignKey('craftnet_packages_developerId_fk', Table::PACKAGES);
        $this->addForeignKey('craftnet_packages_developerId_fk', Table::PACKAGES, ['developerId'], CraftTable::ELEMENTS, ['id']);

        $this->dropForeignKey('craftnet_developerledger_developerId_fk', Table::DEVELOPERLEDGER);
        $this->addForeignKey('craftnet_developerledger_developerId_fk', Table::DEVELOPERLEDGER, ['developerId'], CraftTable::ELEMENTS, ['id']);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220526_183917_add_orgs cannot be reverted.\n";
        return false;
    }

}
