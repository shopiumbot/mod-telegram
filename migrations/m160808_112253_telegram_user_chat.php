<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m160808_112253_telegram_user_chat extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {

/*
        CREATE TABLE IF NOT EXISTS `user_chat` (
    `user_id` bigint COMMENT 'Unique user identifier',
  `chat_id` bigint COMMENT 'Unique user or chat identifier',

  PRIMARY KEY (`user_id`, `chat_id`),

  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
       */


        $this->createTable('{{%telegram__user_chat}}', [
            'user_id' => $this->bigInteger()->comment('Unique user identifier'),
            'chat_id' => $this->bigInteger()->comment('Unique user or chat identifier'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');


        $this->addPrimaryKey('{{%telegram__user_chat_userchar}}', '{{%telegram__user_chat}}', ['user_id','chat_id']);


        $this->addForeignKey(
            'user_id',
            '{{%telegram__user_chat}}',
            'user_id',
            '{{%telegram__user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__user_chat}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m160808_112253_telegram__user_chat was reverted.\n";
    }

}
