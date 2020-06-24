<?php

namespace shopium\mod\telegram\models;

use Longman\TelegramBot\Entities\MessageEntity;
use shopium\mod\telegram\models\query\MessageQuery;

use Longman\TelegramBot\Request;
use panix\engine\CMS;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "actions".
 *
 * @property integer $chat_id
 * @property integer $user_id
 * @property integer $id
 * @property string $message
 * @property string $text
 * @property string $entities
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
        return $this->hasMany(CallbackQuery::class, ['message_id' => 'id'])->orderBy(['created_at'=>SORT_ASC]);
    }


    private $photoCache;

    public function getPhoto()
    {

        try {
            if (!isset($this->photoCache[$this->user_id])) {
                $profile = Request::getUserProfilePhotos(['user_id' => $this->user_id]);

                if ($profile->isOk()) {
                    if ($profile->getResult()->photos) {
                        $photo = $profile->getResult()->photos[0][2];
                        $file = Request::getFile(['file_id' => $photo['file_id']]);
                        if (!file_exists(Yii::getAlias('@app/web/telegram/downloads') . DIRECTORY_SEPARATOR . $file->getResult()->file_path)) {
                            $download = Request::downloadFile($file->getResult());
                        }
                        $this->photoCache[$this->user_id] = $file->getResult()->file_path;
                        return '/telegram/downloads/' . $file->getResult()->file_path;
                    }
                }else{
                    return '/uploads/no-image.jpg';
                }
            }
        } catch (Exception $e) {

        }
        return '/uploads/no-image.jpg';
    }


    public function getMessageObject(){
        return new \Longman\TelegramBot\Entities\Message([
            'chat_id'=>$this->chat_id,
            'text'=>$this->text,
            'entities'=>$this->entities
            ////'entities'=> new MessageEntity([
            //    json_decode($this->entities)
            //    ]
           // )
        ]);
    }



    public function getMessageObject2(){
        return new MessageEntity([
            'chat_id'=>$this->chat_id,
            'text'=>$this->text,

        ]);
    }
}
