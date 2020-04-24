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


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/cart" command
 *
 * Display an inline keyboard with a few buttons.
 */
class FeedbackCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'feedback';
    protected $private_only = true;
    /**
     * @var string
     */
    protected $description = 'Написать нам сообщение';

    /**
     * @var string
     */
    protected $usage = '/feedback';

    /**
     * @var string
     */
    protected $version = '1.0';


    protected $conversation;


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

        $data = [
            'chat_id' => $chat_id,
        ];
        if ($text === '❌ Отмена') {
            $this->telegram->executeCommand('cancel');
            return Request::emptyResponse();
        }
        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $result = Request::emptyResponse();


        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                /*if(mb_strlen($text) >= 10){
                    $notes['state'] = 0;
                    $this->conversation->update();
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $data['text'] = 'Количество символов должно быть больше 10.';
                    $result = Request::sendMessage($data);
                    break;
                }*/
                if ($text === '' || preg_match('/^(\x{2709})/iu', trim($text), $match)) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Напишите сообщение. Оно будет отправлено команде:';
                    //$data['reply_markup'] = Keyboard::remove(['selective' => true]);


                    $keyboards = [[new KeyboardButton('❌ Отмена')]];
                    $data['reply_markup'] = (new Keyboard(['keyboard' => $keyboards]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);


                    if ($text !== '') {
                        $data['text'] = 'Напишите сообщение. Оно будет отправлено команде:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['message'] = $text;
                $text = '';
            // no break
            case 1:
                $notes['state'] = 1;
                $this->conversation->update();
                $out_text = '';
                $out_text .= 'От /whois' . $user_id . PHP_EOL;
                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $out_text .= PHP_EOL . '*' . ucfirst($k) . '*: ' . $v;
                }

                $data['text'] = '✅ Сообщение успешно отправлено! Мы рассмотрим обращение и свяжемся с Вами.';
                $data['reply_markup'] = $this->startKeyboards();
                $this->conversation->stop();

                foreach ($this->telegram->getAdminList() as $admin) {
                    $dataChat['chat_id'] = $admin;
                    $dataChat['parse_mode'] = 'Markdown';
                    $dataChat['disable_notification'] = true;
                    $dataChat['text'] = '*Заявка feedback*: ' . PHP_EOL . $out_text;
                    $resp = Request::sendMessage($dataChat);
                }


                $result = Request::sendMessage($data);


                break;
        }

        return $result;

    }

}
