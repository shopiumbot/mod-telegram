<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use yii\db\Migration;
use shopium\mod\telegram\models\Mailing;

class m000023_000000_mailing extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB';
        $this->createTable(Mailing::tableName(), [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'type' => $this->string(50)->null(),
           // 'user_id' => $this->bigInteger()->notNull(),
            'text' => $this->text()->null(),
            'send_to_groups' => $this->tinyInteger(1)->defaultValue(0),
            'send_to_supergroups' => $this->tinyInteger(1)->defaultValue(0),
            'send_to_channels' => $this->tinyInteger(1)->defaultValue(0),
            'send_to_users' => $this->tinyInteger(1)->defaultValue(0),
            'disable_notification'=>$this->boolean()->defaultValue(1),
            'performer' => $this->string(255)->null(),
            'duration' => $this->integer(11)->unsigned()->null(),
            'title' => $this->string(255)->null(),
            'created_at' => $this->integer(),
        ], $tableOptions);


      //  $this->createIndex('user_id', '{{%telegram_mailing}}', 'user_id');
        $this->createIndex('created_at', Mailing::tableName(), 'created_at');
       // $this->createIndex('message_id', '{{%telegram__mailing}}', 'message_id');

    }

    public function safeDown()
    {
        try {
            $this->dropTable(Mailing::tableName());
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000023_000000_mailing was reverted.\n";
    }

}
