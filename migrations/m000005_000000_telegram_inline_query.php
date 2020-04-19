<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000005_000000_telegram_inline_query extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__inline_query}}', [
            'id' => $this->bigPrimaryKey()->unsigned()->comment('Unique identifier for this query'),
            'user_id'=>$this->bigInteger()->null()->comment('Unique user identifier'),
            'location' => $this->char(255)->null()->defaultValue(NULL)->comment('Location of the user'),
            'query' => $this->text()->notNull()->comment('Text of the query'),
            'offset' => $this->char(255)->defaultValue(NULL)->null()->comment('Offset of the result'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),

        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');

        $this->createIndex('user_id', '{{%telegram__inline_query}}', 'user_id');

        $this->addForeignKey(
            '{{%telegram__inline_query_fk_user_id}}',
            '{{%telegram__inline_query}}',
            'user_id',
            '{{%telegram__user}}',
            'id'
        );
    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__inline_query}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000005_000000_telegram_inline_query was reverted.\n";
    }

}
