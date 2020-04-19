<?php

namespace shopium\mod\telegram\commands\UserCommands;


use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\SystemCommand;
use Yii;

/**
 * Start command
 *
 * Gets executed when a user first starts using the bot.
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Стартовая комманда';

    /**
     * @var string
     */
    protected $usage = '/start';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = true;

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

        $text = Yii::t('telegram/command', 'START', [$user->getFirstName() . ' ' . $user->getLastName()]);

        $data = [
            'parse_mode' => 'HTML',
            'chat_id' => $chat_id,
            'text' => $text,
        ];

        $test =  \shopium\mod\telegram\components\Request::getMyCommands();
        print_r($test);
        $data['reply_markup'] = $this->startKeyboards();


        return Request::sendMessage($data);
    }
}
