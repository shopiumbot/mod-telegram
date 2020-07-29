<?php

namespace shopium\mod\telegram\models;


use panix\engine\CMS;
use panix\engine\Html;
use shopium\mod\telegram\models\query\UserQuery;
use Longman\TelegramBot\Request;
use Yii;
use yii\base\Exception;
use core\components\ActiveRecord;

/**
 * This is the model class for table "actions".
 *
 * @property integer $client_chat_id
 * @property string $message
 * @property string $time
 * @property string $direction
 */
class User extends ActiveRecord
{
    const MODULE_ID = 'telegram';

    public static function find()
    {
        return new UserQuery(get_called_class());
    }

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
            [['first_name', 'last_name', 'username'], 'safe'],
            //   [['message'], 'string', 'max' => 4100],
        ];
    }

    public function getMessages()
    {
        return $this->hasMany(Message::class, ['user_id' => 'id']);
    }

    public function getChats()
    {
        return $this->hasMany(Message::class, ['chat_id' => 'id']);
    }

    public function getLastMessage()
    {
        return $this->hasOne(Message::class, ['user_id' => 'id'])->orderBy(['date' => SORT_DESC]);
    }


    public static function dropdown()
    {
        // get and cache data
        // static $dropdown;
        $dropdown = [];
        //if ($dropdown === null) {

        // get all records from database and generate
        $models = static::find()->where(['is_bot' => 0])->asArray()->all();
        foreach ($models as $model) {
            $name = '';


            if ($model['username']) {
                $name .= '@' . $model['username'];
            } else {
                if ($model['first_name']) {
                    $name .= '' . $model['first_name'] . ' ' . $model['last_name'] . '';
                }
            }
            $dropdown[$model['id']] = $name;
        }
        // }

        return $dropdown;
    }

    public function getPhoto()
    {

        try {
            $profile = Request::getUserProfilePhotos(['user_id' => $this->id]);

            if ($profile->isOk()) {
                if ($profile->getResult()->photos) {
                    $photo = $profile->getResult()->photos[0][2];
                    $file = Request::getFile(['file_id' => $photo['file_id']]);
                    if($file->getOk()){
                        if (!file_exists(Yii::getAlias('@app/web/telegram/downloads') . DIRECTORY_SEPARATOR . $file->getResult()->file_path)) {
                            $download = Request::downloadFile($file->getResult());
                        }
                        return '/telegram/downloads/' . $file->getResult()->file_path;
                    //}else{
                    //    return '/uploads/no-image.jpg';
                    }
                }
          //  } else {
          //      return '/uploads/no-image.jpg';
            }
            return '/uploads/no-image.jpg';
        } catch (Exception $e) {

        }
        return '/uploads/no-image.jpg';
    }

    public function displayName()
    {
        if ($this->username) {
            return $this->username;
        } else {
            if ($this->first_name || $this->last_name) {
                return $this->first_name . ' ' . $this->last_name;
            }
        }
        return null;
    }

    public function displayNameWithUrl()
    {
        if ($this->username) {
            return Html::a('@' . $this->username,'tg://resolve?domain=@'.$this->username);
        } else {
            if ($this->first_name || $this->last_name) {
                return $this->first_name . ' ' . $this->last_name;
            }
        }
        return null;
    }
}
