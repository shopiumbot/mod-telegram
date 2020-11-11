<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;


use Longman\TelegramBot\Entities\Keyboard;
use Yii;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PollOption;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\UserCommand;


/**
 * User "/settings" command
 */
class SettingsCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'settings';
    protected $description = 'setting user profile';
    protected $usage = '/settings <name> <value>';
    protected $version = '1.0.1';
    public $enabled = true;
    public $private_only = true;
    public $show_in_help = false;
    public $notification = true;
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat = $message->getChat();
        $chat_id = $chat->getId();
        $text = trim($message->getText(false));

        //if ($text === $this->keyword_cancel) {
        //    return $this->telegram->executeCommand('settings');
        //}
        $keyboards[] = [
            new KeyboardButton(['text' => Yii::t('telegram/default', 'CHANGE_LANGUAGE')]),
            new KeyboardButton(['text' => Yii::t('telegram/default', '🔔 Уведомление')]), //🔕
        ];
        $keyboards[] = [
            new KeyboardButton(['text' => $this->keyword_cancel]),
        ];


        $reply_markup = (new Keyboard([
            'keyboard' => $keyboards
        ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true)->setSelective(true);


        $data['reply_markup'] = $reply_markup;


        $data['chat_id'] = $chat_id;
        $data['text'] = 'Выберите тип настроек';


        return Request::sendMessage($data);
    }
}
