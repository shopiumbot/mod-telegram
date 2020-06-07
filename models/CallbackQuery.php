<?php

namespace app\modules\telegram\models;

use app\modules\telegram\models\query\CallbackQueryQuery;
use app\modules\user\components\ClientActiveRecord;
use Yii;

/**
 * This is the model class for table "actions".
 *
 * @property integer $client_chat_id
 * @property string $message
 * @property string $time
 * @property string $direction
 */
class CallbackQuery extends ClientActiveRecord
{
    const MODULE_ID = 'telegram';

    public static function find()
    {
        return new CallbackQueryQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%telegram__callback_query}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['client_chat_id'], 'required'],
         //   [['message'], 'string', 'max' => 4100],
        ];
    }
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }


    public function getUser2()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
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
