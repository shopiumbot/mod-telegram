<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use core\modules\shop\models\Product;
use shopium\mod\telegram\components\SystemCommand;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * User "/productswitch" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class ProductswitchCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'productswitch';

    /**
     * @var string
     */
    protected $description = 'Скрыть или показать товар';

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
    public $switch;

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

        if (($this->switch = trim($this->getConfig('switch'))) === '') {
            $this->switch = NULL;
        }

        $message = $this->getMessage();
        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $chat = $message->getChat();
            $user = $callbackQuery->getFrom();
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

        if (!isset($notes['switch']))
            $notes['switch'] = $this->switch;

        if (!isset($notes['callback_message_id']))
            $notes['callback_message_id'] = $update->getCallbackQuery()->getMessage()->getMessageId();

        if (!isset($notes['reply_markup']))
            $notes['reply_markup'] = $update->getCallbackQuery()->getMessage()->getReplyMarkup();


        $product = Product::findOne($notes['id']);

        if ($text === '❌ Отмена') {
            $this->telegram->executeCommand('cancel');
            return Request::emptyResponse();
        }
        //$accept = ($notes['switch']) ? 'Показать' : 'Скрыть';

        $question = (!$this->switch) ? 'Вы уверены что хотите *скрыть* этот товар?' : 'Вы уверены что хотите *показать* этот товар?';
        $result = Request::emptyResponse();
        if ($product) {


            $product = null;
            switch ($state) {
                case 0:
                    if ($text === '' || !in_array($text, ['Да', '❌ Отмена'], true)) {
                        $notes['state'] = 0;
                        $this->conversation->update();

                        $data['parse_mode'] = 'Markdown';
                        $data['reply_markup'] = (new Keyboard(['Да', '❌ Отмена']))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        $data['text'] = $question;
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
                            $product->switch = $notes['switch'];
                            $product->save(false);

                            $message = ($notes['switch']) ? 'Вы успешно показали *' . $product->name . '*.' : 'Вы успешно скрыли *' . $product->name . '*.';
                            //Request::deleteMessage(['chat_id' => $chat_id, 'message_id' => $notes['callback_message_id']]);
                            $result = $this->notify($message, 'success', $this->catalogKeyboards());


                            $keyboards = [];
                            if ($notes['reply_markup']['inline_keyboard']) {
                                foreach ($notes['reply_markup']['inline_keyboard'] as $key => $items) {
                                    if (isset($items[$key])) {
                                        foreach ($items as $item) {
                                            $keyboards[$key][] = new InlineKeyboardButton([
                                                'text' => $item['text'],
                                                'callback_data' => $item['callback_data']
                                            ]);
                                        }

                                    }
                                }
                            }
                            // Удаляем старые админские кнопки
                            unset($keyboards[count($keyboards) - 1]);

                            if ($keyboards) {
                                $dataEdit['chat_id'] = $chat_id;
                                $dataEdit['message_id'] = $notes['callback_message_id'];
                                $dataEdit['reply_markup'] = new InlineKeyboard([
                                    'inline_keyboard' => ArrayHelper::merge($keyboards, [$this->productAdminKeywords($chat_id, $product)])
                                ]);
                                $edit = Request::editMessageReplyMarkup($dataEdit);


                                if (!$edit->isOk()) {
                                    $this->notify('Error: ' . $edit->getDescription());
                                }
                            }
                            $this->conversation->stop();

                        }
                    }

                    return $result;
                    break;
            }
        } else {
            $result = $this->notify('Товар не найден!');
        }


        return $result;
    }
}
