<?php

namespace shopium\mod\telegram\components\Commands\AdminCommands;



use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\AdminCommand;
use shopium\mod\telegram\models\User;
use Yii;

/**
 * User "/adminadd" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class AdminAddCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'adminadd';

    /**
     * @var string
     */
    protected $description = 'Добававить администратора';

    /**
     * @var string
     */
    protected $usage = '/adminadd';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;
    protected $show_in_help = false;

    /**
     * Conversation Object
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $update = $this->getUpdate();

        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $user = $callbackQuery->getFrom();
            parse_str($callbackQuery->getData(), $params);
            if (isset($params['command'])) {
                if ($params['command'] == 'changeProductImage') {
                    $callbackData = 'changeProductImage';
                }
            }
            if (isset($params['query'])) {
                if ($params['query'] == 'addCart') {
                    $callbackData = $params['query'];
                } elseif ($params['query'] == 'deleteInCart') {
                    $callbackData = $params['query'];
                } elseif ($params['query'] == 'productSpinner') {
                    $callbackData = $params['query'];
                }
            }

        } else {
            $message = $this->getMessage();
            $user = $message->getFrom();
        }
        $chat = $message->getChat();
        $chat_id = $chat->getId();
        $user_id =  $user->getId();

        $text = trim($message->getText(true));
        $data['chat_id'] = $chat_id;

        //Preparing Response


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

                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Введите ID администратора:';
                    if ($text !== '') {
                        $data['text'] = 'ID администратора, должен быть числом:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['admin_id'] = $text;
                $text = '';
            // no break
            case 1:
                $this->conversation->update();

                $user = User::findOne($notes['admin_id']);

                $content = '✅ !!!'.$user->id . PHP_EOL;

                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $content .= PHP_EOL . '<strong>' . ucfirst($k) . '</strong>: ' . $v;
                }

                $data['parse_mode'] = 'HTML';
                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['text'] = $content;
                $this->conversation->stop();

                $result = Request::sendMessage($data);
                break;
        }

        return $result;
    }
}
