<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m160808_112253_telegram_user extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__user}}', [
            'id' => $this->bigPrimaryKey()->comment('Unique identifier for this user or bot'),
            'is_bot' => $this->tinyInteger(1)->defaultValue(1)->comment('True, if this user is a bot'),
            'first_name' => $this->char(255)->notNull()->defaultValue('')->comment('User or bot first name'),
            'last_name' => $this->char(255)->notNull()->defaultValue('')->comment('User or bot last name'),
            'username' => $this->char(255)->notNull()->defaultValue('')->comment('User or bot username'),
            'language_code' => $this->char(10)->defaultValue(NULL)->comment('IETF language tag of the user\'s language'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),
            'updated_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date update'),
            /**
             * //`id` bigint COMMENT 'Unique identifier for this user or bot',
             * //`is_bot` tinyint(1) DEFAULT 0 COMMENT 'True, if this user is a bot',
             * `first_name` CHAR(255) NOT NULL DEFAULT '' COMMENT 'User''s or bot''s first name',
             * `last_name` CHAR(255) DEFAULT NULL COMMENT 'User''s or bot''s last name',
             * `username` CHAR(191) DEFAULT NULL COMMENT 'User''s or bot''s username',
             * `language_code` CHAR(10) DEFAULT NULL COMMENT 'IETF language tag of the user''s language',
             * //`created_at` timestamp NULL DEFAULT NULL COMMENT 'Entry date creation',
             * //`updated_at` timestamp NULL DEFAULT NULL COMMENT 'Entry date update',
             */
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');


        // $this->addPrimaryKey('tlgrm_auth_mngr_chats_PK', '{{%tlgrm_auth_mngr_chats}}', 'chat_id');


        $this->createIndex('username', '{{%telegram__user}}', 'username');
    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__user}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m160808_112253_telegram_user was reverted.\n";
    }

}
