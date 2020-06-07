<?php

namespace shopium\mod\telegram\models;

use panix\mod\cart\components\events\EventProduct;
use panix\mod\cart\components\HistoricalBehavior;
use panix\mod\cart\models\Delivery;
use Yii;

/**
 * This is the model class for table "actions".
 *
 * @property integer $chat_id
 * @property string $action
 * @property string $param
 */
class Order extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tg_order}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->user->getClientDb();
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

    /**
     * Relation
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(OrderProduct::class, ['order_id' => 'id']);
    }

    /**
     * Relation
     * @return int|string
     */
    public function getProductsCount()
    {
        return $this->hasMany(OrderProduct::class, ['order_id' => 'id'])->count();
    }
    public function updateTotalPrice()
    {

        $this->total_price = 0;
        $products = OrderProduct::find()->where(['order_id' => $this->id])->all();

        foreach ($products as $product) {
            /** @var OrderProduct $product */

          //  $currency_rate = Yii::$app->currency->active['rate'];
            $currency_rate = 1;
            if ($product->originalProduct) {
                $this->total_price += $product->price * $currency_rate * $product->quantity;
            }

        }

        /*if($this->promoCode){
            if ('%' === substr($this->promoCode->discount, -1, 1)) {
                $this->total_price -= $this->total_price * ((double) $this->promoCode->discount) / 100;
            }

        }*/

        $this->save(false);
    }


    public function addProduct($product, $quantity, $price)
    {

        if (!$this->isNewRecord) {
            $image = NULL;

            $imageData = $product->getImage();
            if($imageData){
                $image = "/uploads/store/product/{$product->id}/".basename($imageData->getPathToOrigin());
            }else{
                $image = '/uploads/no-image.jpg';
            }

            $ordered_product = new OrderProduct();
            $ordered_product->order_id = $this->id;
            $ordered_product->product_id = $product->id;
            $ordered_product->image = $image;
            //$ordered_product->client_id = $this->client_id;
           // $ordered_product->currency_id = $product->currency_id;
            $ordered_product->name = $product->name;
            $ordered_product->quantity = $quantity;
         //   $ordered_product->sku = $product->sku;
            $ordered_product->price = $price;
            return $ordered_product->save();
        }
        return false;
    }

}
