<?php

namespace shopium\mod\telegram\models;


use shopium\mod\telegram\models\query\UserQuery;
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

        ];
    }

    public function getPhoto()
    {

        try {
            $profile = Request::getUserProfilePhotos(['user_id' => $this->id]);


            if ($profile->getResult()->photos) {
                $photo = $profile->getResult()->photos[0][2];
                $file = Request::getFile(['file_id' => $photo['file_id']]);
                if (!file_exists(Yii::getAlias('@app/web/downloads/telegram') . DIRECTORY_SEPARATOR . $file->getResult()->file_path)) {
                    $download = Request::downloadFile($file->getResult());
                }
                return '/telegram/downloads/' . $file->getResult()->file_path;
            }
        } catch (Exception $e) {

        }
        return '/uploads/no-image.jpg';
    }
}
