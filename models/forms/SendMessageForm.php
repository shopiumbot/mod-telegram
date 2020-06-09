<?php

namespace shopium\mod\telegram\models\forms;


use Longman\TelegramBot\Request;
use Yii;
use yii\base\Model;

class SendMessageForm extends Model
{


    public $text;
    public $user_id;

    public function rules()
    {
        return [
            [['text', 'user_id'], "required"],
            [['text'], 'string'],
        ];
    }

    public function send()
    {
        $data['chat_id'] = $this->user_id;
        $data['text'] = $this->text;
        $request = Request::sendMessage($data);
        if ($request->isOk()) {
            return true;
        } else {
            return false;
        }
    }

    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'text' => 'Сообщение',
        ];
    }
}
