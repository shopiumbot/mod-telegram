<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use yii\db\Migration;

class m000022_000000_feedback extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB';
        $this->createTable('{{%feedback}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'message_id' => $this->bigInteger()->unsigned(),
            'user_id' => $this->bigInteger()->notNull(),
            'text' => $this->text()->notNull(),
            'created_at' => $this->integer(),
        ], $tableOptions);


        $this->createIndex('user_id', '{{%feedback}}', 'user_id');
        $this->createIndex('created_at', '{{%feedback}}', 'created_at');
        $this->createIndex('message_id', '{{%feedback}}', 'message_id');

    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%feedback}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000022_000000_feedback was reverted.\n";
    }

}
