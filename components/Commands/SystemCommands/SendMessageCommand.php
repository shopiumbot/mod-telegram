<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\cart\models\Order;
use shopium\mod\telegram\components\SystemCommand;
use Yii;

/**
 *
 * This command cancels the currently active conversation and
 * returns a message to let the user know which conversation it was.
 * If no conversation is active, the returned message says so.
 */
class SendMessageCommand extends SystemCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'sendmessage';
    protected $description = 'send message';

    protected $version = '1.0.0';
    protected $conversation;
   // public $enabled = true;
    public $private_only = true;
    public $user_id;


    /**
     * {@inheritdoc}
     */
    public function execute()
    {

        if (($this->user_id = $this->getConfig('user_id')) === '') {
            $this->user_id = false;
        }

        $update = $this->getUpdate();

        $callback_query = $update->getCallbackQuery();
        $message = $callback_query->getMessage();
        $chat = $message->getChat();
        $user = $message->getFrom();

        $chat_id = $chat->getId();
        $user_id = $user->getId();


        $callback_query_id = $callback_query->getId();
        $callback_data = $callback_query->getData();

        $config = Yii::$app->settings->get('app');








        $text = trim($message->getText(false));

$this->notify($text);


        if ($text === static::KEYWORD_CANCEL) {
            return $this->telegram->executeCommand('cancel');
            //    return Request::emptyResponse();
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
                if ($text === '' || $text === static::KEYWORD_CANCEL || preg_match('/^(\x{2709})/iu', $text, $match)) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Напишите сообщение. Оно будет отправлено команде:';
                    //$data['reply_markup'] = Keyboard::remove(['selective' => true]);


                    $keyboards[] = [new KeyboardButton(static::KEYWORD_CANCEL)];
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
                $message = $notes['message'];
                $out_text .= PHP_EOL . '*Сообщение*: ' . $message;
                $data['text'] = '✅ *Сообщение успешно отправлено!*' . PHP_EOL;
                $data['text'] .= 'Мы рассмотрим обращение и свяжемся с Вами.';
                $data['parse_mode'] = 'Markdown';
                $data['reply_markup'] = $this->startKeyboards();

                $this->conversation->stop();


                $result = Request::sendMessage($data);
                /*if ($result->isOk()) {
                    $fb = new Feedback();
                    $fb->text = $message;
                    $fb->user_id = $user_id;
                    if($fb->validate()){
                        $fb->save();
                    }

                }*/
                break;
        }
        return $result;









        if ($this->user_id) {
            $data['chat_id'] = $this->user_id;
            $data['parse_mode'] = 'Markdown';
            $data['disable_notification'] = true;
            $data['text'] = '*Заявка:* ';
           /* $data['reply_markup'] = new InlineKeyboard([
                'inline_keyboard' => $keyboards
            ]);*/
            return Request::sendMessage($data);

        }
        return $this->notify('Ошибка #1003');

    }


}
