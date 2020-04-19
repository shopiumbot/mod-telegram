<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000010_000000_telegram_shipping_query extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__shipping_query}}', [

            'id' => $this->bigPrimaryKey()->unsigned()->comment('Unique query identifier'),
            'user_id' => $this->bigInteger()->comment('User who sent the query'),
            'invoice_payload' => $this->char(255)->notNull()->defaultValue('')->comment('Bot specified invoice payload'),
            'shipping_address' => $this->char(255)->notNull()->defaultValue('')->comment('User specified shipping address'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),

        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');

        $this->createIndex('user_id', '{{%telegram__shipping_query}}', 'user_id');

        $this->addForeignKey(
            '{{%telegram__shipping_query_fk_user_id}}',
            '{{%telegram__shipping_query}}',
            'user_id',
            '{{%telegram__user}}',
            'id'
        );


    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__shipping_query}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000010_000000_telegram_shipping_query was reverted.\n";
    }

}
