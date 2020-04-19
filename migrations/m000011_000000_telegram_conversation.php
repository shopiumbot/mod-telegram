<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000011_000000_telegram_conversation extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__conversation}}', [
            'id' => $this->bigPrimaryKey(20)->unsigned()->comment('Unique identifier for this entry'),
            'user_id' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Unique user identifier'),
            'chat_id' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Unique user or chat identifier'),
            'status' => 'ENUM("active", "cancelled", "stopped") NOT NULL DEFAULT "active" COMMENT "Conversation state"',
            'command' => $this->string(160)->defaultValue('')->comment('Default command to execute'),
            'notes' => $this->text()->defaultValue(NULL)->comment('Data stored from command'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),
            'updated_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date update'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');



        $this->createIndex('user_id', '{{%telegram__conversation}}', 'user_id');
        $this->createIndex('chat_id', '{{%telegram__conversation}}', 'chat_id');
        $this->createIndex('status', '{{%telegram__conversation}}', 'status');

        $this->addForeignKey(
            '{{%telegram__conversation_fk_user_id}}',
            '{{%telegram__conversation}}',
            'user_id',
            '{{%telegram__user}}',
            'id'
        );
        $this->addForeignKey(
            '{{%telegram__conversation_fk_chat_id}}',
            '{{%telegram__conversation}}',
            'chat_id',
            '{{%telegram__chat}}',
            'id'
        );
    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__conversation}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000011_000000_telegram_conversation was reverted.\n";
    }

}
