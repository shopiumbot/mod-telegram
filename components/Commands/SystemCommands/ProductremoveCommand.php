<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use core\modules\shop\models\Product;
use shopium\mod\telegram\components\SystemCommand;
use Yii;

/**
 * User "/productremove" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class ProductremoveCommand extends SystemCommand
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


        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $user = $callbackQuery->getFrom();
        } else {
            $message = $this->getMessage();
            $user = $message->getFrom();

        }
        $chat = $message->getChat();
        $chat_id = $chat->getId();
        $user_id = $user->getId();
        $this->setLanguage($user_id);
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


        $product = Product::findOne((int)$notes['id']);

        if ($text === Yii::t('yii', 'No')) {
            return $this->telegram->executeCommand('cancel');
        }

        if ($product) {

            $result = Request::emptyResponse();
            // $product = null;
            switch ($state) {
                case 0:
                    if ($text === '' || !in_array($text, [Yii::t('yii', 'Yes'), Yii::t('yii', 'No')], true)) {
                        $notes['state'] = 0;
                        $this->conversation->update();
                        $data['parse_mode'] = 'Markdown';
                        $data['reply_markup'] = (new Keyboard([Yii::t('yii', 'Yes'), Yii::t('yii', 'No')]))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        $data['text'] = Yii::t('telegram/default', 'PRODUCT_DELETE', $product->name);
                        if ($text !== '') {
                            $data['text'] = Yii::t('telegram/default', 'SELECT_VARIANT');
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
                        $productName = $product->name;
                        if ($product->delete()) {
                            Request::deleteMessage(['chat_id' => $chat_id, 'message_id' => $notes['callback_message_id']]);
                            $result = $this->notify(Yii::t('telegram/default', 'PRODUCT_DELETE_SUCCESS', $productName), 'success', $this->catalogKeyboards());
                        } else {
                            $result = $this->notify('Error #9993', 'error', $this->catalogKeyboards());
                        }
                    }
                    $this->conversation->stop();
                    break;
            }
        } else {
            $result = $this->notify(Yii::t('shop/default', 'NOT_FOUND_PRODUCT'));
        }


        return $result;
    }
}
