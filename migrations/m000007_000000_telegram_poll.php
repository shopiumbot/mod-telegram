<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000007_000000_telegram_poll extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__poll}}', [
            'id' => $this->bigPrimaryKey()->unsigned()->comment('Unique poll identifier'),
            'question' => $this->char(255)->notNull()->comment('Poll question'),
            'options' => $this->text()->notNull()->comment('List of poll options'),
            'is_closed' => $this->tinyInteger(1)->defaultValue(0)->comment('True, if the poll is closed'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),

        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');

    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__poll}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000007_000000_telegram_poll was reverted.\n";
    }

}
