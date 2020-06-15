<?php

namespace shopium\mod\telegram\commands\UserCommands;

use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\BotCommand;
use Longman\TelegramBot\Entities\File;
use Longman\TelegramBot\Entities\InputMedia\InputMediaPhoto;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Entities\Poll;
use Longman\TelegramBot\Entities\PollAnswer;
use Longman\TelegramBot\Entities\PollOption;
use Longman\TelegramBot\Entities\UserProfilePhotos;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
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
    protected $need_mysql = true;

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

        $adsData['chat_id'] = $chat_id;
        $adsData['parse_mode'] = 'Markdown';
        $adsData['text'] = 'Бот работает на платформе 🥇 @shopiumbot' . PHP_EOL;
        $adsData['text'] .= '👉 https://shopiumbot.com' . PHP_EOL;
        $ads = Request::sendMessage($adsData);
        if($ads->isOk()){
            $db = DB::insertMessageRequest($ads->getResult());
        }



        //$adsData2['chat_id']=343987970;
        //$adsData2['parse_mode']='Markdown';
        //$adsData2['text']='test message';
        //$ads2 = Request::sendMessage($adsData2);



        /*$answer = new PollAnswer([
            'id' => '5420566903024254986',
        ]);
        $pp = new Poll([
            'id' => '5420566903024254986',
            'question' => 'Оцените бота',
            'options' => new PollOption(['text'=>'test','voter_count'=>4]),
        ]);
        $this->notify(json_encode($answer->getOptionIds()));*/

        /*$dataPoll = [
            'chat_id' => $chat_id,
            'question' => 'Оцените бота',
            'is_anonymous' => false,
            'type' => 'regular', //quiz, regular
            'allows_multiple_answers' => false,
            'options' => json_encode(['👍 Классно','👌 Нормально','👎 Не очень'])
        ];
        $poll = Request::sendPoll($dataPoll);
        if(!$poll->isOk()){
            $this->notify($poll->getDescription());
        }else{
            $this->notify('ok');
        }*/
      //  print_r($poll);
        //$test = Request::getMyCommands();
        // print_r($test);
        $data['reply_markup'] = $this->startKeyboards();

        $response = Request::sendMessage($data);
        if($response->isOk()){
            $db = DB::insertMessageRequest($response->getResult());
        }
        return $response;
    }


}
