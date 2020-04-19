<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000008_000000_telegram_callback_query extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__callback_query}}', [


            'id' => $this->bigPrimaryKey()->unsigned()->comment('Unique identifier for this query'),
            'user_id'=>$this->bigInteger()->null()->comment('Unique user identifier'),
            'chat_id' => $this->bigInteger()->null()->comment('Unique chat identifier'),
            'message_id' => $this->bigInteger()->unsigned()->comment('Unique message identifier'),
            'inline_message_id' => $this->char(255)->null()->defaultValue(NULL)->comment('Identifier of the message sent via the bot in inline mode, that originated the query'),
            'chat_instance' => $this->char(255)->null()->defaultValue(NULL)->comment('Global identifier, uniquely corresponding to the chat to which the message with the callback button was sent'),
            'data' => $this->char(255)->notNull()->defaultValue('')->comment('Data associated with the callback button'),
            'game_short_name' => $this->char(255)->notNull()->defaultValue('')->comment('Short name of a Game to be returned, serves as the unique identifier for the game'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),

        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');

        $this->createIndex('user_id', '{{%telegram__callback_query}}', 'user_id');
        $this->createIndex('chat_id', '{{%telegram__callback_query}}', 'chat_id');
        $this->createIndex('message_id', '{{%telegram__callback_query}}', 'message_id');

        $this->addForeignKey(
            '{{%telegram__callback_query_fk_user_id}}',
            '{{%telegram__callback_query}}',
            'user_id',
            '{{%telegram__user}}',
            'id'
        );


        $this->addForeignKey(
            '{{%telegram__callback_query_fk_chat_message_id}}',
            '{{%telegram__callback_query}}',
            ['chat_id','message_id'],
            '{{%telegram__message}}',
            ['chat_id','id']
        );



    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__callback_query}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000008_000000_telegram_callback_query was reverted.\n";
    }

}
