<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000020_000000_telegram_chosen_inline_result extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__chosen_inline_result}}', [
            'id' => $this->bigPrimaryKey()->unsigned()->comment('Unique identifier for this query'),
            'user_id'=>$this->bigInteger()->null()->comment('The user that chose the result'),
            'result_id' => $this->char(255)->notNull()->defaultValue('')->comment('The unique identifier for the result that was chosen'),
            'location' => $this->char(255)->null()->defaultValue(NULL)->comment('Sender location, only for bots that require user location'),
            'inline_message_id' => $this->char(255)->null()->defaultValue(NULL)->null()->comment('Identifier of the sent inline message'),
            'query' => $this->text()->notNull()->comment('The query that was used to obtain the result'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),

        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');

        $this->createIndex('user_id', '{{%telegram__chosen_inline_result}}', 'user_id');

        $this->addForeignKey(
            '{{%telegram__chosen_inline_result_fk_user_id}}',
            '{{%telegram__chosen_inline_result}}',
            'user_id',
            '{{%telegram__user}}',
            'id'
        );

    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__chosen_inline_result}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000020_000000_telegram_chosen_inline_result was reverted.\n";
    }

}
