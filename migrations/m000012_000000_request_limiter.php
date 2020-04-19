<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000012_000000_request_limiter extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__request_limiter}}', [
            'id' => $this->bigPrimaryKey(20)->unsigned()->comment('Unique identifier for this entry'),
            'chat_id' => $this->char(255)->null()->defaultValue(NULL)->comment('Unique chat identifier'),
            'inline_message_id' => $this->char(255)->null()->defaultValue(NULL)->comment('Identifier of the sent inline message'),
            'method' => $this->char(255)->defaultValue(NULL)->comment('Request method'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');

    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__request_limiter}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000012_000000_request_limiter was reverted.\n";
    }

}
