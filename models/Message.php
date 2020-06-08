<?php

namespace shopium\mod\telegram\models;

use shopium\mod\telegram\models\query\MessageQuery;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
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
class Message extends ActiveRecord
{
    const MODULE_ID = 'telegram';

    public static function find()
    {
        return new MessageQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%telegram__message}}';
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


    public function getCallback()
    {
        return $this->hasOne(CallbackQuery::class, ['message_id' => 'id']);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

        ];
    }

    private $photoCache;

    public function getPhoto()
    {

        try {
            if (!isset($this->photoCache[$this->user_id])) {
                $profile = Request::getUserProfilePhotos(['user_id' => $this->user_id]);

                if ($profile) {
                    if (isset($profile->getResult()->photos) && isset($profile->getResult()->photos[0])) {
                        $photo = $profile->getResult()->photos[0][2];
                        $file = Request::getFile(['file_id' => $photo['file_id']]);
                        if (!file_exists(Yii::getAlias('@app/web/downloads/telegram') . DIRECTORY_SEPARATOR . $file->getResult()->file_path)) {
                            $download = Request::downloadFile($file->getResult());
                        }
                        $this->photoCache[$this->user_id] = $file->getResult()->file_path;
                        return '/downloads/telegram/' . $file->getResult()->file_path;
                    }
                }
            }
        } catch (Exception $e) {

        }
        return '/uploads/no-image.jpg';
    }
}
