<?php

namespace shopium\mod\telegram\models;


use shopium\mod\telegram\models\query\ChatQuery;
use core\components\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "tbl_start_source".
 *
 * @property integer $user_id
 * @property string $source
 * @property integer $created_at
 */
class StartSource extends ActiveRecord
{
    const MODULE_ID = 'telegram';
    public $usersCount; //for dataProvider query

    public static function find()
    {
        return new ChatQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%telegram__start_source}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['source'], 'safe'],
            [['source'], 'string', 'max' => 255],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at'],
                ]
            ]
        ];
    }
}
