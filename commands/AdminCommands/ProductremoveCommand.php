<?php

namespace shopium\mod\telegram\commands\AdminCommands;


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use core\modules\shop\models\Product;
use shopium\mod\telegram\components\AdminCommand;
use Yii;

/**
 * User "/productremove" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class ProductremoveCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'productremove';

    /**
     * @var string
     */
    protected $description = 'Удаление товара';

    /**
     * @var string
     */
    protected $usage = '/productremove <id>';

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
    public $id;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {

        if (($this->id = trim($this->getConfig('id'))) === '') {
            $this->id = NULL;
        }

        $message = $this->getMessage();
        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $chat = $message->getChat();
            $user = $callbackQuery->getFrom();
            // parse_str($callbackQuery->getData(), $params);
            // echo $this->id . PHP_EOL;
            // print_r($params);
            // die;
        } else {
            $message = $this->getMessage();
            $chat = $message->getChat();
            $user = $message->getFrom();

        }
        $chat_id = $chat->getId();
        $user_id = $user->getId();
        $text = trim($message->getText(false));
        $data['chat_id'] = $chat_id;


        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }
        if ($this->id)
            $notes['id'] = $this->id;

        if (!isset($notes['callback_message_id']))
            $notes['callback_message_id'] = $update->getCallbackQuery()->getMessage()->getMessageId();


        $product = Product::findOne($notes['id']);

        if ($text === 'Нет') {
            $this->telegram->executeCommand('cancel');
            return Request::emptyResponse();
        }

        if ($product) {

            $result = Request::emptyResponse();
            $product = null;
            switch ($state) {
                case 0:
                    if ($text === '' || !in_array($text, ['Да', 'Нет'], true)) {
                        $notes['state'] = 0;
                        $this->conversation->update();

                        $data['reply_markup'] = (new Keyboard(['Да', 'Нет']))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        $data['text'] = 'Вы уверены что хотите удалить этот товар?';
                        if ($text !== '') {
                            $data['text'] = 'Выберите вариант!';
                        }


                        $result = Request::sendMessage($data);
                        break;
                    }
                    $notes['state'] = 1;
                    $this->conversation->update();
                    $text = '';
                // no break
                case 1:
                    if ($notes['state']) {
                        $product = Product::findOne((int)$notes['id']);
                        if ($product) {
                            if($product->delete()){
                                Request::deleteMessage(['chat_id' => $chat_id, 'message_id' => $notes['callback_message_id']]);
                                $result = $this->notify('Вы успешно удалили *' . $product->name . '*.', 'success', $this->catalogKeyboards());
                            }

                        }
                    }
                    $this->conversation->stop();
                    break;
            }
        } else {
            $result = $this->notify('Товар не найден!');
        }


        return $result;
    }
}
