<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000009_000000_telegram_pre_checkout_query extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__pre_checkout_query}}', [
            'id' => $this->bigPrimaryKey()->unsigned()->comment('Unique query identifier'),
            'user_id' => $this->bigInteger()->comment('User who sent the query'),
            'currency' => $this->char(3)->comment('Three-letter ISO 4217 currency code'),
            'total_amount' => $this->bigInteger()->comment('Total price in the smallest units of the currency'),
            'invoice_payload' => $this->char(255)->notNull()->defaultValue('')->comment('Bot specified invoice payload'),
            'shipping_option_id' => $this->char(255)->null()->comment('Identifier of the shipping option chosen by the user'),
            'order_info' => $this->text()->null()->comment('Order info provided by the user'),
            'created_at' => $this->timestamp()->null()->defaultValue(NULL)->comment('Entry date creation'),

        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');

        $this->createIndex('user_id', '{{%telegram__pre_checkout_query}}', 'user_id');

        $this->addForeignKey(
            '{{%telegram__pre_checkout_query_fk_user_id}}',
            '{{%telegram__pre_checkout_query}}',
            'user_id',
            '{{%telegram__user}}',
            'id'
        );


    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__pre_checkout_query}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000009_000000_telegram_pre_checkout_query was reverted.\n";
    }

}
