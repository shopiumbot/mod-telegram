<?php

namespace shopium\mod\telegram\models;

use shopium\mod\telegram\Telegram;
use Yii;

/**
 * This is the model class for table "actions".
 *
 * @property integer $client_chat_id
 * @property string $message
 * @property string $time
 * @property string $direction
 */
class User extends \yii\db\ActiveRecord
{
    const MODULE_ID = 'telegram';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%telegram__user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['client_chat_id'], 'required'],
            [['first_name','last_name','username'], 'safe'],
         //   [['message'], 'string', 'max' => 4100],
        ];
    }
    public function getMessages()
    {
        return $this->hasMany(User::class, ['id' => 'user_id']);
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
