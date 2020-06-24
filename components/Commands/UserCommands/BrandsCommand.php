<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;


use core\modules\shop\models\Manufacturer;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;
use core\modules\shop\models\Category;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/brands" command
 *
 * Display an inline keyboard with a few buttons.
 */
class BrandsCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'brands';

    /**
     * @var string
     */
    protected $description = 'Бренды';

    /**
     * @var string
     */
    protected $usage = '/brands';

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * @var string
     */
    public $id;

    public $private_only = false;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $update = $this->getUpdate();

        $isCallback = false;
        if ($update->getCallbackQuery()) {
            $isCallback = true;
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
        $data['chat_id'] = $chat_id;
        if (($this->id = trim($this->getConfig('id'))) === '') {
            $this->id = null;
        }

        if ($this->id) {
            $items = Manufacturer::findOne($this->id);
        } else {
            $items = Manufacturer::find()->published()->all();
        }
        // $root = Category::findOne($this->id);


        $keyboards = [];
        if ($items) {
            foreach ($items as $item) {
                $count = $item->productsCount;
                if ($count) {
                    $keyboards[] = new InlineKeyboardButton([
                        'text' => $item->name . ' (' . $count . ')',
                        'callback_data' => 'query=getList&model=brands&id=' . $item->id
                    ]);
                }
            }

        } else {
            return $this->notify('Нет информации', 'info');
        }

        $keyboards = array_chunk($keyboards, 2);
        if ($isCallback) {
            $keyboards[] = [new InlineKeyboardButton([
                'text' => '↩ Вернуться в каталог',
                'callback_data' => 'query=openCatalog&id=1'
            ])];

            $data['message_id'] = $message->getMessageId();
            $data['reply_markup'] = new InlineKeyboard([
                'inline_keyboard' => $keyboards
            ]);
            $request = Request::editMessageReplyMarkup($data);
            if (!$request->isOk()) {
                return $this->notify($chat_id . ' editcatalog:' . $message->getMessageId() . ': ' . $request->getDescription(), 'error');
            }
            return $request;
        } else {
            $data['text'] = 'Выберите раздел:';
            $data['reply_markup'] = new InlineKeyboard([
                'inline_keyboard' => $keyboards
            ]);


            $dataCatalog['text'] = '⬇ Список брендов';
            $dataCatalog['chat_id'] = $chat_id;
            $dataCatalog['reply_markup'] = $this->catalogKeyboards();
            $buttonsResponse = Request::sendMessage($dataCatalog);

            if ($buttonsResponse->isOk()) {
                $db = DB::insertMessageRequest($buttonsResponse->getResult());
            }
            //  $result = $data;
        }

        $response = Request::sendMessage($data);
        if ($response->isOk()) {
            $db = DB::insertMessageRequest($response->getResult());
        }


        return $response;
    }
}
