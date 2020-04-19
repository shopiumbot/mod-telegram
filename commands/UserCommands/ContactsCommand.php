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
 * User "/contacts" command
 *
 * Display an inline keyboard with a few buttons.
 */
class ContactsCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'contacts';

    /**
     * @var string
     */
    protected $description = 'Контактная информация';

    /**
     * @var string
     */
    protected $usage = '/contacts';

    /**
     * @var string
     */
    protected $version = '1.0';

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
        $data['text'] = '*Контактная информация*'.PHP_EOL.PHP_EOL;
        $data['text'] .= '📞 Телефон: *+38 063 489 26 95*'.PHP_EOL.PHP_EOL;
        $data['text'] .= '✉ Почта: *info@pixelion.com.ua*'.PHP_EOL.PHP_EOL;
        $data['text'] .= '🌍 Адрес: *Украина, г.Одесса, ул. Малая Арнаутская 36*'.PHP_EOL.PHP_EOL;
        $data['parse_mode'] = 'Markdown';

        $data['reply_markup'] = $this->homeKeyboards();


        return Request::sendMessage($data);

    }

}
