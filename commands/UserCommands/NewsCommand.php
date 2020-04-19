<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace shopium\mod\telegram\commands\UserCommands;


use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/cart" command
 *
 * Display an inline keyboard with a few buttons.
 */
class NewsCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'news';

    /**
     * @var string
     */
    protected $description = 'Список новостей';

    /**
     * @var string
     */
    protected $usage = '/news';

    /**
     * @var string
     */
    protected $version = '1.0';
    public $enabled=false;

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

        $sticker = [
            'chat_id' => $chat_id,
            'sticker' => 'CAACAgIAAxkBAAJBQl6QlXE_01q3-LiWldLrnvAuPpkwAAIRAAOQ_ZoVIPDREeqfP5cYBA'
        ];
        Request::sendSticker($sticker);

        $data['chat_id'] = $chat_id;
        $data['text'] = $this->description.' в разработке';
        $data['reply_markup'] = $this->homeKeyboards();


      //  $s=Request::sticker(['name'=>'Hot Cherry']);
//print_r($s);


        return Request::sendMessage($data);

    }

}
