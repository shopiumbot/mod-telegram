<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000006_000000_telegram_edited_message extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__edited_message}}', [
            /**




             *
             *
             */
            'id' => $this->bigPrimaryKey()->unsigned()->comment('Unique identifier for this entry'),
            'user_id'=>$this->bigInteger()->null()->comment('Unique user identifier'),
            'chat_id'=>$this->bigInteger()->comment('Unique chat identifier'),
            'message_id' => $this->bigInteger()->unsigned()->comment('Unique message identifier'),
            'edit_date' => $this->timestamp()->null()->defaultValue(NULL)->comment('Date the message was edited in timestamp format'),
            'text' => $this->text()->comment('For text messages, the actual UTF-8 text of the message max message length 4096 char utf8'),
            'entities' => $this->text()->comment('For text messages, special entities like usernames, URLs, bot commands, etc. that appear in the text'),
            'caption' => $this->text()->comment('For message with caption, the actual UTF-8 text of the caption'),

        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');

        $this->createIndex('chat_id', '{{%telegram__edited_message}}', 'chat_id');
        $this->createIndex('message_id', '{{%telegram__edited_message}}', 'message_id');
        $this->createIndex('user_id', '{{%telegram__edited_message}}', 'user_id');



        $this->addForeignKey(
            '{{%telegram__edited_message_fk_chat_id}}',
            '{{%telegram__edited_message}}',
            'chat_id',
            '{{%telegram__chat}}',
            'id'
        );

        $this->addForeignKey(
            '{{%telegram__edited_message_fk_user_id}}',
            '{{%telegram__edited_message}}',
            'user_id',
            '{{%telegram__user}}',
            'id'
        );

        $this->addForeignKey(
            '{{%telegram__edited_message_fk_message_id}}',
            '{{%telegram__edited_message}}',
            ['chat_id','message_id'],
            '{{%telegram__message}}',
            ['chat_id','id']
        );

    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__edited_message}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000006_000000_telegram_edited_message was reverted.\n";
    }

}
