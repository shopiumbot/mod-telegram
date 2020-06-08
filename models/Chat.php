<?php

namespace shopium\mod\telegram\models;


use shopium\mod\telegram\models\query\ChatQuery;
use Longman\TelegramBot\Request;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "actions".
 *
 * @property integer $client_chat_id
 * @property string $message
 * @property string $time
 * @property string $direction
 */
class Chat extends ActiveRecord
{
    const MODULE_ID = 'telegram';

    public static function find()
    {
        return new ChatQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%telegram__chat}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['client_chat_id'], 'required'],
            [['first_name', 'last_name', 'username'], 'safe'],
            //   [['message'], 'string', 'max' => 4100],
        ];
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

        ];
    }

}
