<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;


use core\modules\pages\models\Pages;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/cart" command
 *
 * Display an inline keyboard with a few buttons.
 */
class PagesCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'pages';

    /**
     * @var string
     */
    protected $description = 'Список странц';

    /**
     * @var string
     */
    protected $usage = '/pages';

    /**
     * @var string
     */
    protected $version = '1.0';
 //   public $enabled=false;

    // public $enabled = false;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();



        $data['chat_id'] = $chat_id;
      //  $data['text'] = json_encode($names);


        return Request::sendMessage($data);

    }

}
