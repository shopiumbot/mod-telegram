<?php

namespace shopium\mod\telegram\models\forms;


use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Yii;
use yii\base\Model;

class SendAllMessageForm extends Model
{


    public $text;
    public $send_to_users;
    public $send_to_groups;
    public $send_to_supergroups;
    public $send_to_channels;

    public function rules()
    {
        return [
            [['text'], 'required'],
            [['send_to_users', 'send_to_groups', 'send_to_supergroups', 'send_to_channels'], 'boolean'],
            [['text'], 'string'],
        ];
    }

    public function send()
    {
        $api = Yii::$app->telegram;

        /** @var ServerResponse[] $results */
        $results = Request::sendToActiveChats(
            'sendMessage',     //callback function to execute (see Request.php methods)
            [
                'text' => $this->text
            ],
            [
                'groups' => $this->send_to_groups,
                'supergroups' => $this->send_to_supergroups,
                'channels' => $this->send_to_channels,
                'users' => $this->send_to_users,
            ]
        );

        return true;

    }

    public function attributeLabels()
    {
        return [
            'text' => 'Сообщение',
            'send_to_users' => 'Все пользователи',
            'send_to_groups' => 'Все группы',
            'send_to_supergroups' => 'Все супер-группы',
            'send_to_channels' => 'Все каналы',
        ];
    }
}
