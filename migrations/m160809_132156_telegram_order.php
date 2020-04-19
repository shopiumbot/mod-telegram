<?php

namespace shopium\mod\telegram\migrations;

use shopium\mod\telegram\models\Order;
use shopium\mod\telegram\models\OrderProduct;
use panix\engine\db\Migration;

class m160809_132156_telegram_order extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable(Order::tableName(), [
            'id' => $this->primaryKey(),
            'client_id' => $this->string(62),
            'username' => $this->string(100),
            'firstname' => $this->string(100)->null(),
            'lastname' => $this->string(100)->null(),
            'phone' => $this->string(50)->null(),
            'delivery' => $this->string(50)->null(),
            'payment' => $this->string(50)->null(),
            'delivery_id' => $this->integer(),
            'payment_id' => $this->integer(),
            'total_price' => $this->money(10,2),
            'checkout' => $this->boolean()->defaultValue(0),
            'pay' => $this->boolean()->defaultValue(0),
        ]);
        $this->createIndex('client_id', Order::tableName(), 'client_id');

        $this->createTable(OrderProduct::tableName(), [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer(11),
            'product_id' => $this->integer(11),
            'name' => $this->string(255),
            'image' => $this->string(255),
            'quantity' => $this->smallInteger(8),
            'price' => $this->money(10, 2),
            // 'param' => $this->string(62)
        ]);
        $this->createIndex('order_id', OrderProduct::tableName(), 'order_id');
        $this->createIndex('product_id', OrderProduct::tableName(), 'product_id');
    }

    public function safeDown()
    {

        $this->dropTable(Order::tableName());
        $this->dropTable(OrderProduct::tableName());
    }

}
