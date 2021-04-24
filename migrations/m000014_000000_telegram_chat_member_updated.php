<?php

//namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000014_000000_telegram_chat_member_updated extends Migration
{

    public function safeUp()
    {


        $this->createTable('{{%telegram__chat_member_updated}}', [
            'id' => $this->bigPrimaryKey()->comment('Unique identifier for this entry'),
            'chat_id' => $this->bigInteger()->comment('Chat the user belongs to'),
            'user_id' => $this->bigInteger()->comment('Performer of the action, which resulted in the change'),
            'date' => $this->timestamp()->comment('Date the change was done in Unix time'),
            'old_chat_member' => $this->text()->comment('Previous information about the chat member'),
            'new_chat_member' => $this->text()->comment('New information about the chat member'),
            'invite_link' => $this->text()->comment('Chat invite link, which was used by the user to join the chat; for joining by invite link events only'),
            'created_at' => $this->timestamp()->null()->comment('Entry date creation'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');


        $this->addForeignKey(
            '{{%telegram__chat_member_updated_fk_user_id}}',
            '{{%telegram__chat_member_updated}}',
            'user_id',
            '{{%telegram__user}}',
            'id'
        );


        $this->addForeignKey(
            '{{%telegram__chat_member_updated_fk_chat_id}}',
            '{{%telegram__chat_member_updated}}',
            'chat_id',
            '{{%telegram__chat}}',
            'id'
        );
    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__chat_member_updated}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000014_000000_telegram_chat_member_updated was reverted.\n";
    }

}
