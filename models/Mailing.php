<?php

namespace shopium\mod\telegram\models;

use panix\engine\CMS;
use Yii;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InputMedia\InputMediaPhoto;
use Longman\TelegramBot\Request;
use panix\engine\components\ImageHandler;
use shopium\mod\telegram\components\Telegram;
use shopium\mod\telegram\models\query\MailingQuery;
use core\components\ActiveRecord;
use yii\behaviors\TimestampBehavior;


/**
 * This is the model class for table "tbl_mailing".
 *
 * @property integer $user_id
 * @property boolean $disable_notification
 * @property boolean $send_to_groups
 * @property boolean $send_to_supergroups
 * @property boolean $send_to_channels
 * @property boolean $send_to_users
 * @property boolean $send_to_admins
 * @property string $type
 * @property string $text
 * @property string performer
 * @property integer duration
 * @property string title
 */
class Mailing extends ActiveRecord
{
    const MODULE_ID = 'telegram';
    public $media;
    public $thumb;

    public static function find()
    {
        return new MailingQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%telegram__mailing}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[['text'], 'required'],
            [['text', 'type', 'media', 'thumb'], 'safe'],
            [['disable_notification', 'send_to_groups', 'send_to_supergroups', 'send_to_channels', 'send_to_users', 'send_to_admins'], 'boolean'],
            [['text'], 'string', 'max' => 4100],
        ];
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

    public static function typeList()
    {
        return [
            'sendMessage' => self::t('TYPE_SEND_MESSAGE'),
            'sendPhoto' => self::t('TYPE_SEND_PHOTO'),
            'sendAudio' => self::t('TYPE_SEND_AUDIO'),
            'sendDocument' => self::t('TYPE_SEND_DOCUMENT'),
            'sendVideo' => self::t('TYPE_SEND_VIDEO'),
            'sendMediaGroup' => self::t('TYPE_SEND_GALLERY'),
            //'sendVoice' => 'Голосовае',
            'sendVenue' => self::t('TYPE_SEND_VENUE')
        ];
    }


    public function afterSave($insert, $changedAttributes)
    {
        $path = Yii::getAlias('@uploads/tmp') . DIRECTORY_SEPARATOR;

        $chatsQuery = Chat::find()->select('id');
        $adminsQuery = clone $chatsQuery;
        $where = [];
        $chats=[];
        $admins=[];
        if ($this->send_to_users)
            $where[] = 'private';
        if ($this->send_to_groups)
            $where[] = 'group';
        if ($this->send_to_supergroups)
            $where[] = 'supergroup';
        if ($this->send_to_channels)
            $where[] = 'channel';


        if ($where){
            $chatsQuery->where(['in', 'type', $where]);
            $chats = $chatsQuery->asArray()->all();
        }
        if ($this->send_to_admins) {
            $adminsQuery->where(['id' => Yii::$app->user->getBotAdmins()]);
            $admins = $adminsQuery->asArray()->all();
        }


        $ids = array_unique(array_merge($admins,$chats), SORT_REGULAR);


        /** @var Telegram $api */
        $api = Yii::$app->telegram;
        $results = [];
        $data = [];
        $senders = [];

        if ($ids) {

            $text = 'text';

            if ($this->type == 'sendPhoto') {
                $text = 'caption';
                $data['photo'] = $path . $this->media[0];
            } elseif ($this->type == 'sendDocument') {
                $text = 'caption';
                $data['document'] = $path . $this->media[0];
            } elseif ($this->type == 'sendAudio') {
                $text = 'caption';
                $data['audio'] = $path . $this->media[0];
                if ($this->title)
                    $data['title'] = $this->title;
                if ($this->duration)
                    $data['duration'] = $this->duration;
                if ($this->performer)
                    $data['performer'] = $this->performer;
                if ($this->thumb) {
                    /** @var ImageHandler $img */
                    $img = Yii::$app->img;
                    $img->load($path . $this->thumb);
                    $img->resize(320, 320);
                    $img->save();
                    $data['thumb'] = 'https://' . Yii::$app->request->getHostName() . '/uploads/tmp/' . $this->thumb;
                }

            } elseif ($this->type == 'sendMediaGroup') {
                $text = 'caption';

              //  if (is_array($this->media)) {
                    $media = [];
                    if($this->media){
                    foreach ($this->media as $key => $file) {
                        $item=[];
                        //$data['media_file_'.$i] = [];
                        if (file_exists($path . $file)) {
                            //$data['photo_'.$i] = Request::encodeFile($file);
                            //$item['media'] = 'attach://' . $file->tempName;
                            $item['media'] = 'https://' . Yii::$app->request->getHostName() . '/uploads/tmp/' . $file;
                            if($this->text)
                                $item['caption'] = $this->text;

                            $media[] = new InputMediaPhoto($item);
                        }
                    }
                }

                $data['media'] = $media;

                  //  CMS::dump($data);die;
            } elseif ($this->type == 'sendVenue') {
                $data['latitude'] = $this->latitude;
                $data['longitude'] = $this->longitude;
                if ($this->address)
                    $data['address'] = $this->address;
                if ($this->title)
                    $data['title'] = $this->title;
            }
            if($this->text)
                $data[$text] = $this->text;

            $data['disable_notification'] = !$this->disable_notification;

            $keyboards = [];
            $btn_data = [];
            $buttons = json_decode($this->buttons, true);

            if ($buttons) {
                foreach ($buttons as $btn) {

                    $btn_data['text'] = $btn['label'];
                    $isUrl = preg_match('/http(s?)\:\/\//i', $btn['callback']);

                    if($isUrl){
                        $btn_data['url'] = $btn['callback'];
                    }else{
                        $btn_data['callback_data'] = $btn['callback'];
                    }
                    $keyboards[] = [
                        new InlineKeyboardButton($btn_data)
                    ];
                }
            }


            foreach ($ids as $row) {
                $data['chat_id'] = $row['id'];
                if ($keyboards) {
                    $data['reply_markup'] = new InlineKeyboard([
                        'inline_keyboard' => $keyboards
                    ]);
                }
                $results[] = Request::send($this->type, $data);
            }
        }


        if($results){
            foreach ($results as $res){
                /** @var \Longman\TelegramBot\Entities\ServerResponse $res */
                if(!$res->getOk()){
                    Yii::$app->session->addFlash('telegram-error', $res->getDescription());
                }
            }
        }

        if ($this->media) {
            foreach ($this->media as $file) {
                if (file_exists($path . $file)) {
                    unlink($path . $file);
                }
            }
        }
        if ($this->thumb) {
            if (file_exists($path . $this->thumb)) {
                unlink($path . $this->thumb);
            }
        }


        parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub
    }

}
