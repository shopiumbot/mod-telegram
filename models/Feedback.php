<?php

namespace shopium\mod\telegram\models;


use shopium\mod\telegram\models\query\ChatQuery;
use core\components\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "tbl_feedback".
 *
 * @property integer $user_id
 * @property string $text
 */
class Feedback extends ActiveRecord
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
        return '{{%feedback}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['text'], 'safe'],
            [['text'], 'string', 'max' => 4100],
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
