<?php

namespace shopium\mod\telegram\commands\UserCommands;

use Longman\TelegramBot\Entities\BotCommand;
use Longman\TelegramBot\Entities\Poll;
use Longman\TelegramBot\Entities\PollOption;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
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

        $adsData['chat_id']=$chat_id;
        $adsData['parse_mode']='Markdown';
        $adsData['text']='Бот работает на платформе 🥇 @shopiumbot'.PHP_EOL;
        $adsData['text'].='👉 https://shopiumbot.com'.PHP_EOL;
        $ads = Request::sendMessage($adsData);


        //$adsData2['chat_id']=343987970;
        //$adsData2['parse_mode']='Markdown';
        //$adsData2['text']='test message';
        //$ads2 = Request::sendMessage($adsData2);



        /*$cmd = Request::setMyCommands([
            'commands' => [
                new BotCommand([
                    'command' => 'start',
                    'description' => 'Start command'
                ]),
                new BotCommand([
                    'command' => 'help',
                    'description' => 'Помощь'
                ]),
            ]
        ]);


        $pp = new Poll([
            'id' => CMS::gen(11),
            'question' => 'test?',
            'options' => new PollOption(['text'=>'test','voter_count'=>0]),
        ]);

        $poll = Request::sendPoll([
            'chat_id' => $chat_id,
            'question' => 'test?',
            'options' => json_encode(['text','test','voter_count']),
        ]);
        print_r($poll);*/
        //$test = Request::getMyCommands();
       // print_r($test);
        $data['reply_markup'] = $this->startKeyboards();


        return Request::sendMessage($data);
    }
}
