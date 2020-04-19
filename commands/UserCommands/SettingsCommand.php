<?php

namespace shopium\mod\telegram\commands\UserCommands;


use Longman\TelegramBot\Entities\PollOption;
use Longman\TelegramBot\Entities\User;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\commands\pager\InlineKeyboardPagination;
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
    public $show_in_help=false;
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

        //echo $text . ' - ' . $text2 . PHP_EOL;

        $dataPoll = [
            'chat_id' => $chat_id,
            'question' => 'Test Poll',
            'is_anonymous' => false,
            'type' => 'quiz', //quiz, regular
            'allows_multiple_answers' => false,
            //'options'=>['test','test2']
            'options' => new PollOption(['text'=>'asddsadsa','voter_count'=>5])
        ];



        /*  $results = Request::sendToActiveChats(
              'sendMessage', // Callback function to execute (see Request.php methods)
              ['text' => $chat->getFirstName().' '.$chat->getLastName().' @'.$chat->getUsername().'! go go go!'], // Param to evaluate the request
              [
                  'groups'      => true,
                  'supergroups' => true,
                  'channels'    => false,
                  'users'       => true,
              ]
          );*/
       // echo $dataPoll['options'].PHP_EOL;

        $pollRequest = Request::sendPoll($dataPoll);

print_r($pollRequest);







        $data = [
            'chat_id' => $chat_id,
            'text' => $pollRequest->toJson(),
        ];


        //$pollOption = new PollOption(['text'=>'test1','voter_count'=>0]);




    return Request::sendMessage($data);
    }
}
