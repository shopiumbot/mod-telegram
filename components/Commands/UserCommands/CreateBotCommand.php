<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;

use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\UserCommand;

/**
 * User "/createbot" command
 */
class CreateBotCommand extends UserCommand
{

    protected $name = 'createbot';
    protected $description = 'Создание аккаунта бота';
    protected $usage = '/createbot';
    protected $version = '1.0';
    protected $show_in_help = false;
    protected $enabled=false;

    /**
     * {@inheritdoc}
     */
    public function execute(): ServerResponse
    {
        $message     = $this->getMessage();
        $chat_id     = $message->getChat()->getId();
        $text = trim($message->getText(true));

        $data = [
            'chat_id'    => $chat_id,
            'parse_mode' => 'markdown',
        ];


        $data['text'] = 'Помощь не доступна: Команда';

        return Request::sendMessage($data);
    }

}
