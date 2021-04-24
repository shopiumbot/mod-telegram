<?php

//namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000021_000000_telegram_start_source extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__start_source}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'user_id' => $this->bigInteger()->notNull(),
            'source' => $this->string(255)->notNull(),
            'created_at' => $this->integer(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');


        $this->createIndex('user_id', '{{%telegram__start_source}}', 'user_id');
        $this->createIndex('source', '{{%telegram__start_source}}', 'source');
        $this->createIndex('created_at', '{{%telegram__start_source}}', 'created_at');

    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__start_source}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000021_000000_telegram_start_source was reverted.\n";
    }

}
