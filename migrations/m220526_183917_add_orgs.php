<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
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
        $this->renameTable('{{%craftnet_developers}}', Table::ORGS);

        $fields = Craft::$app->getFields();

        $this->addColumn(Table::ORGS, 'displayName', $fields->getFieldByHandle('developerName')->getContentColumnType());
        $this->addColumn(Table::ORGS, 'websiteSlug', $this->string());
        $this->addColumn(Table::ORGS, 'websiteUrl', $fields->getFieldByHandle('developerUrl')->getContentColumnType());
        $this->addColumn(Table::ORGS, 'location', $fields->getFieldByHandle('location')->getContentColumnType());
        $this->addColumn(Table::ORGS, 'supportPlan', $fields->getFieldByHandle('supportPlan')->getContentColumnType());
        $this->addColumn(Table::ORGS, 'supportPlanExpiryDate', $fields->getFieldByHandle('supportPlanExpiryDate')->getContentColumnType());
        $this->addColumn(Table::ORGS, 'enablePartnerFeatures', $this->boolean()->defaultValue(false)->notNull());
        $this->addColumn(Table::ORGS, 'enableDeveloperFeatures', $this->boolean()->defaultValue(false)->notNull());

        // TODO: investigate field usage: purchasedPlugins

        $this->createTable(Table::ORGS_MEMBERS, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'orgId' => $this->integer()->notNull(),
            'admin' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::ORGS_MEMBERS, ['userId'], \craft\db\Table::USERS, ['id'], 'CASCADE');
        $this->addForeignKey(null, Table::ORGS_MEMBERS, ['orgId'], Table::ORGS, ['id'], 'CASCADE');
        $this->createIndex(null, Table::ORGS_MEMBERS, ['userId', 'orgId'], true);

        $this->createTable(Table::ORGS_ORDERS, [
            'id' => $this->integer()->notNull(),
            'orgId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::ORGS_ORDERS, ['id'], \craft\commerce\db\Table::ORDERS, ['id'], 'CASCADE');
        $this->addForeignKey(null, Table::ORGS_ORDERS, ['orgId'], Table::ORGS, ['id'], 'CASCADE');

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
