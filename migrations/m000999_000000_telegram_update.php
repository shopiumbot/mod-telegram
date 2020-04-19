<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000999_000000_telegram_update extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {


        /**


        ALTER TABLE `telegram_update` ADD FOREIGN KEY (`poll_answer_poll_id`) REFERENCES `poll_answer` (`poll_id`);
         */
        $this->createTable('{{%telegram__telegram_update}}', [
            'id' => $this->bigPrimaryKey()->unsigned()->comment('Update\'s unique identifier'),
            'chat_id' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Unique chat identifier'),
            'message_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('New incoming message of any kind - text, photo, sticker, etc.'),
            'edited_message_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('New version of a message that is known to the bot and was edited'),
            'channel_post_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('New incoming channel post of any kind - text, photo, sticker, etc.'),
            'edited_channel_post_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('New version of a channel post that is known to the bot and was edited'),
            'inline_query_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('New incoming inline query'),
            'chosen_inline_result_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('The result of an inline query that was chosen by a user and sent to their chat partner'),
            'callback_query_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('New incoming callback query'),
            'shipping_query_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('New incoming shipping query. Only for invoices with flexible price'),
            'pre_checkout_query_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('New incoming pre-checkout query. Contains full information about checkout'),
            'poll_id' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('New poll state. Bots receive only updates about polls, which are sent or stopped by the bot'),
            'poll_answer_poll_id'=>$this->bigInteger()->unsigned()->defaultValue(NULL)->comment('A user changed their answer in a non-anonymous poll. Bots receive new votes only in polls that were sent by the bot itself.')
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');


        $this->createIndex('message_id', '{{%telegram__telegram_update}}', 'message_id');
        $this->createIndex('chat_message_id', '{{%telegram__telegram_update}}', ['chat_id', 'message_id']);
        $this->createIndex('edited_message_id', '{{%telegram__telegram_update}}', 'edited_message_id');
        $this->createIndex('channel_post_id', '{{%telegram__telegram_update}}', 'channel_post_id');
        $this->createIndex('edited_channel_post_id', '{{%telegram__telegram_update}}', 'edited_channel_post_id');
        $this->createIndex('inline_query_id', '{{%telegram__telegram_update}}', 'inline_query_id');
        $this->createIndex('chosen_inline_result_id', '{{%telegram__telegram_update}}', 'chosen_inline_result_id');
        $this->createIndex('callback_query_id', '{{%telegram__telegram_update}}', 'callback_query_id');
        $this->createIndex('shipping_query_id', '{{%telegram__telegram_update}}', 'shipping_query_id');
        $this->createIndex('pre_checkout_query_id', '{{%telegram__telegram_update}}', 'pre_checkout_query_id');
        $this->createIndex('poll_id', '{{%telegram__telegram_update}}', 'poll_id');
        $this->createIndex('poll_answer_poll_id', '{{%telegram__telegram_update}}', 'poll_answer_poll_id');

        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_poll_answer_poll_id}}',
            '{{%telegram__telegram_update}}',
            'poll_answer_poll_id',
            '{{%telegram__poll_answer}}',
            'poll_id'
        );

        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_poll_id}}',
            '{{%telegram__telegram_update}}',
            'poll_id',
            '{{%telegram__poll}}',
            'id'
        );

        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_pre_checkout_query_id}}',
            '{{%telegram__telegram_update}}',
            'pre_checkout_query_id',
            '{{%telegram__pre_checkout_query}}',
            'id'
        );


        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_shipping_query_id}}',
            '{{%telegram__telegram_update}}',
            'shipping_query_id',
            '{{%telegram__shipping_query}}',
            'id'
        );


        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_callback_query_id}}',
            '{{%telegram__telegram_update}}',
            'callback_query_id',
            '{{%telegram__callback_query}}',
            'id'
        );

        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_chosen_inline_result_id}}',
            '{{%telegram__telegram_update}}',
            'chosen_inline_result_id',
            '{{%telegram__chosen_inline_result}}',
            'id'
        );

        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_inline_query_id}}',
            '{{%telegram__telegram_update}}',
            'inline_query_id',
            '{{%telegram__inline_query}}',
            'id'
        );

        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_edited_channel_post_id}}',
            '{{%telegram__telegram_update}}',
            'edited_channel_post_id',
            '{{%telegram__edited_message}}',
            'id'
        );





        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_chat_id_channel_post_id}}',
            '{{%telegram__telegram_update}}',
            ['chat_id','channel_post_id'],
            '{{%telegram__message}}',
            ['chat_id','id']
        );


        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_chat_message_id}}',
            '{{%telegram__telegram_update}}',
            ['chat_id','message_id'],
            '{{%telegram__message}}',
            ['chat_id','id']
        );

        $this->addForeignKey(
            '{{%telegram__telegram_update_fk_edited_message_id}}',
            '{{%telegram__telegram_update}}',
            'edited_message_id',
            '{{%telegram__edited_message}}',
            'id'
        );

    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__telegram_update}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000999_000000_telegram_update was reverted.\n";
    }

}
