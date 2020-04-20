<?php

namespace shopium\mod\telegram\models;

use core\modules\shop\models\Product;
use Yii;

/**
 * This is the model class for table "actions".
 *
 * @property integer $chat_id
 * @property string $action
 * @property string $param
 */
class OrderProduct extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%telegram_order__product}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        $db = \Yii::$app->controller->module->db;
        return Yii::$app->get($db);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
           // [['chat_id'], 'required'],
           // [['chat_id'], 'integer'],
           // [['action', 'param'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'chat_id' => 'User ID',
            'action' => 'Action',
            'param' => 'Parameter',
        ];
    }
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }
    public function getOriginalProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
    public function afterDelete()
    {
        if ($this->order) {
            $this->order->updateTotalPrice();
           // $this->order->updateDeliveryPrice();
        }

        return parent::afterDelete();
    }
    public function afterSave($insert, $changedAttributes)
    {
        $this->order->updateTotalPrice();
       // $this->order->updateDeliveryPrice();

        if ($this->isNewRecord) {
            $product = Product::findOne($this->product_id);
            $product->decreaseQuantity();
        }

        return parent::afterSave($insert, $changedAttributes);
    }
}
