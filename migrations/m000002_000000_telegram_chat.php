<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000002_000000_telegram_chat extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__chat}}', [

            'id' => $this->bigPrimaryKey()->comment('Unique identifier for this chat'),
            'type' => "ENUM('private', 'group', 'supergroup', 'channel') NOT NULL COMMENT 'Type of chat, can be either private, group, supergroup or channel'",
            'title'=>$this->char(255)->defaultValue(NULL)->comment('Title, for supergroups, channels and group chats'),
            'first_name' => $this->char(255)->defaultValue(NULL)->comment('First name of the other party in a private chat'),
            'last_name' => $this->char(255)->defaultValue(NULL)->comment('Last name of the other party in a private chat'),
            'username' => $this->char(255)->defaultValue(NULL)->comment('Username, for private chats, supergroups and channels if available'),
            'all_members_are_administrators' => $this->tinyInteger(1)->defaultValue(0)->comment('True if a all members of this group are admins'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),
            'updated_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date update'),
            'old_id' => $this->bigInteger()->defaultValue(NULL)->comment('Unique chat identifier, this is filled when a group is converted to a supergroup'),

        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');

        $this->createIndex('old_id', '{{%telegram__chat}}', 'old_id');
    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__chat}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000002_000000_telegram_chat was reverted.\n";
    }

}
