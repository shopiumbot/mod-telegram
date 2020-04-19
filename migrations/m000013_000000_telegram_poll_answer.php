<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000013_000000_telegram_poll_answer extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__poll_answer}}', [
            'poll_id' => $this->bigPrimaryKey()->unsigned()->comment('Unique poll identifier'),
            'user_id' => $this->bigInteger()->notNull()->comment('The user, who changed the answer to the poll'),
            'option_ids' => $this->text()->notNull()->comment('0-based identifiers of answer options, chosen by the user. May be empty if the user retracted their vote.'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),

        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');


        $this->addForeignKey(
            '{{%telegram__poll_answer_fk_poll_id}}',
            '{{%telegram__poll_answer}}',
            'poll_id',
            '{{%telegram__poll}}',
            'id'
        );
    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__poll_answer}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000013_000000_telegram_poll_answer was reverted.\n";
    }

}
