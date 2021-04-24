<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\UserCommand;
use shopium\mod\telegram\models\Feedback;
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
    protected $usage = '/feedback <string>';

    /**
     * @var string
     */
    protected $version = '1.0';


    protected $conversation;

    public function getDescription():string
    {
        return Yii::t('telegram/default', 'COMMAND_FEEDBACK');
    }

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $data['chat_id'] = $chat_id;
        if ($text === $this->keyword_cancel) {
            return $this->telegram->executeCommand('cancel');
            //    return Request::emptyResponse();
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
                if ($text === '' || $text === $this->keyword_cancel || preg_match('/^(\x{2709})/iu', $text, $match)) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = Yii::t('telegram/default','WRITE_MESSAGE').':';
                    //$data['reply_markup'] = Keyboard::remove(['selective' => true]);


                    $keyboards[] = [new KeyboardButton($this->keyword_cancel)];
                    $data['reply_markup'] = (new Keyboard(['keyboard' => $keyboards]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);


                    if ($text !== '') {
                        $data['text'] = Yii::t('telegram/default','WRITE_MESSAGE').':';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['message'] = $text;
                $text = '';
            // no break
            case 1:
                $notes['state'] = 1;
                unset($notes['state']);
                $this->conversation->update();
                $content_text = '';
                $content_text .= Yii::t('telegram/default','FROM').' /whois' . $user_id . PHP_EOL;

                $message = $notes['message'];
                $content_text .= PHP_EOL . '*'.Yii::t('telegram/default','MESSAGE').'*: ' . $message;
                $data['text'] = '✅ *'.Yii::t('telegram/default','SEND_MESSAGE_SUCCESS').'*' . PHP_EOL;
                $data['text'] .= Yii::t('telegram/default','FEEDBACK_TEXT');
                $data['parse_mode'] = 'Markdown';
                $data['reply_markup'] = $this->startKeyboards();

                $this->conversation->stop();


                foreach ($this->telegram->getAdminList() as $admin) {
                    $dataChat['chat_id'] = $admin;
                    $dataChat['parse_mode'] = 'Markdown';
                    $dataChat['disable_notification'] = true;
                    $dataChat['text'] = '*'.Yii::t('telegram/default','FEEDBACK_TITLE').':* ' . PHP_EOL . $content_text;
                    //$dataChat['reply_markup'] = new InlineKeyboard([
                    //    'inline_keyboard' => $keyboards
                    //]);
                    $resp = Request::sendMessage($dataChat);


                    if ($resp->isOk()) {
                        $fb = new Feedback();
                        $fb->text = $message;
                        $fb->user_id = $user_id;
                        $fb->message_id = $resp->getResult()->getMessageId();
                        if ($fb->validate()) {
                            $fb->save();


                            $keyboards[] = [
                                new InlineKeyboardButton([
                                    'text' => "Ответить {$user_id}&fid={$fb->id}",
                                    'callback_data' => "query=sendMessage&user_id={$user_id}&fid={$fb->id}"
                                ])
                            ];
                            $dataEdit['chat_id'] = $chat_id;
                            $dataEdit['message_id'] = $resp->getResult()->getMessageId();
                            /*$dataEdit['reply_markup'] = new InlineKeyboard([
                                'inline_keyboard' => $keyboards
                            ]);*/
                            Request::editMessageReplyMarkup($dataEdit);


                        }

                    }
                }

                $result = Request::sendMessage($data);

                break;
        }
        return $result;
    }

}
