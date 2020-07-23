<?php

namespace shopium\mod\telegram\components;

use core\modules\pages\models\Pages;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Yii;

abstract class Command extends \Longman\TelegramBot\Commands\Command
{
    public $orderHistoryCount = 0;
    public $orderProductCount = 0;
    public $settings;

    const KEYWORD_BACK = '⬅ Назад';
    const KEYWORD_CANCEL = '❌ Отмена';
    const KEYWORD_ADMIN = '💎 Админ-панель';

    public function __construct(Api $telegram, Update $update = null)
    {
        /*$this->orderHistoryCount = Order::find()
            ->where(['checkout' => 1, 'user_id' => $update->getMessage()->getFrom()->getId()])
            ->count();
        $orderProductCount = Order::find()
            ->where(['checkout' => 0, 'user_id' => $update->getMessage()->getFrom()->getId()])
            ->one();
        if ($orderProductCount) {
            $this->orderProductCount = $orderProductCount->productsCount;
        }*/
        $this->settings = Yii::$app->settings->get('app');
        parent::__construct($telegram, $update);
    }

    public function preExecute()
    {
        if (time() > $this->telegram->getUser()->expire) {
            $text = '*Бот отключен!*' . PHP_EOL;
            $text .= 'Период использование бота окончен, для разблокировки бота, необходимо продлить тарифный план.';
            return $this->notify($text);
        }

        parent::preExecute();

    }

    public function isSystemCommand()
    {
        return ($this instanceof SystemCommand);
    }

    /**
     * If this is an AdminCommand
     *
     * @return bool
     */
    public function isAdminCommand()
    {
        return ($this instanceof AdminCommand);
    }

    /**
     * If this is a UserCommand
     *
     * @return bool
     */
    public function isUserCommand()
    {
        return ($this instanceof UserCommand);
    }


    public function productAdminKeywords($chat_id, $product)
    {
        $keyboards = [];
        if ($this->telegram->isAdmin($chat_id)) {
            $keyboards = [
                new InlineKeyboardButton([
                    'text' => '✏',
                    'callback_data' => 'query=productUpdate&id=' . $product->id
                ]),
                new InlineKeyboardButton([
                    'text' => ($product->switch) ? '🟢 скрыть' : '🔴 показать',
                    'callback_data' => 'query=productSwitch&id=' . $product->id . '&switch=' . (($product->switch) ? 0 : 1)
                ]),
                new InlineKeyboardButton([
                    'text' => '❌',
                    'callback_data' => 'query=productDelete&id=' . $product->id
                ]),
            ];
        }
        return $keyboards;
    }

    public function startKeyboards()
    {
        $textMyOrders = $this->settings->button_text_history;
        $textMyCart = $this->settings->button_text_cart;
        // if ($this->orderHistoryCount) {
        // $textMyOrders .= ' (' . $this->orderHistoryCount . ')';
        // }
        //if ($this->orderProductCount) {
        //$textMyCart .= ' (' . $this->orderProductCount . ')';
        //}


        $keyboards[] = [
            new KeyboardButton(['text' => $this->settings->button_text_catalog]),
            new KeyboardButton(['text' => $this->settings->button_text_search]),
            new KeyboardButton(['text' => $textMyCart])
        ];
        $keyboards[] = [
            //  new KeyboardButton(['text' => '📢 Новости']),
            new KeyboardButton(['text' => $textMyOrders]),
            new KeyboardButton(['text' => '❓ Помощь'])
        ];


        if (in_array(812367093, $this->telegram->getAdminList())) {
            $keyboards[] = [
                new KeyboardButton(['text' => self::KEYWORD_ADMIN])
            ];
        }
        // $keyboards[] = [
        //  new KeyboardButton(['text' => '⚙ Настройки']),
        //   new KeyboardButton(['text' => '❓ Помощь'])
        // ];
        $pages = Pages::find()->published()->asArray()->all();
        $pagesKeywords = [];
        foreach ($pages as $page) {
            $pagesKeywords[] = new KeyboardButton(['text' => $page['name']]);

        }
        if ($pagesKeywords) {
            $keyboards[] = $pagesKeywords;
        }
        $data = (new Keyboard([
            'keyboard' => $keyboards
        ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true)->setSelective(true);

        return $data;
    }

    public function number_format($sum)
    {
        return number_format($sum, 2, '.', ' ');
    }

    public function errorMessage($message = null)
    {
        if ($this->getUpdate()->getCallbackQuery()) {
            $data['chat_id'] = $this->getUpdate()->getCallbackQuery()->getMessage()->getChat()->getId();
        } else {
            $data['chat_id'] = $this->getUpdate()->getMessage()->getChat()->getId();
        }
        $data['text'] = ($message) ? $message : 'Ошибка';
        return Request::sendMessage($data);
    }

    public function notify($message = null, $type = 'info', $reply_markup = false)
    {
        if (!in_array($type, ['info', 'success', 'error', 'warning'])) {
            $type = 'info';
        }
        if ($type == 'success') {
            $preText = '*✅ Успех:*' . PHP_EOL;
        } elseif ($type == 'error') {
            $preText = '*🚫 Ошибка:*' . PHP_EOL;
        } elseif ($type == 'warning') {
            $preText = '*⚠ Внимание:*' . PHP_EOL;
        } else {
            $preText = '*ℹ Информация:*' . PHP_EOL;
        }
        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {
            $data['chat_id'] = $update->getCallbackQuery()->getMessage()->getChat()->getId();
        } else {
            $data['chat_id'] = $update->getMessage()->getChat()->getId();
        }
        $data['parse_mode'] = 'Markdown';
        $data['text'] = $preText . ' ' . $message . '';
        if ($reply_markup) {
            $data['reply_markup'] = $reply_markup;
        }
        $response = Request::sendMessage($data);

        if ($response->isOk()) {
            $db = DB::insertMessageRequest($response->getResult());
        }

        return $response;
    }

    public function catalogKeyboards()
    {

        $textMyOrders = $this->settings->button_text_history;
        $textMyCart = $this->settings->button_text_cart;
        // if ($this->orderHistoryCount) {
        //    $textMyOrders .= ' (' . $this->orderHistoryCount . ')';
        //}
        //if ($this->orderProductCount) {
        //    $textMyCart .= ' (' . $this->orderProductCount . ')';
        //}

        $keyboards[] = [
            new KeyboardButton(['text' => $this->settings->button_text_start]),
            new KeyboardButton(['text' => $this->settings->button_text_catalog]),
            new KeyboardButton(['text' => $this->settings->button_text_search]),
        ];

        $keyboards[] = [
            new KeyboardButton(['text' => $textMyCart]),
            new KeyboardButton(['text' => $textMyOrders]),
            new KeyboardButton(['text' => '❓ Помощь'])
        ];
        if (in_array(812367093, $this->telegram->getAdminList())) {
            $keyboards[] = [
                new KeyboardButton(['text' => self::KEYWORD_ADMIN])
            ];
        }
        $pages = Pages::find()->published()->asArray()->all();
        $pagesKeywords = [];
        foreach ($pages as $page) {
            $pagesKeywords[] = new KeyboardButton(['text' => $page['name']]);

        }
        if ($pagesKeywords) {
            $keyboards[] = $pagesKeywords;
        }
        $data = (new Keyboard([
            'keyboard' => $keyboards
        ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true)->setSelective(true);

        return $data;
    }

    public function homeKeyboards()
    {

        $keyboards[] = [
            new KeyboardButton(['text' => $this->settings->button_text_start]),
            new KeyboardButton(['text' => $this->settings->button_text_catalog]),
            new KeyboardButton(['text' => $this->settings->button_text_search]),
        ];

        $data = (new Keyboard([
            'keyboard' => $keyboards
        ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true)->setSelective(true);

        return $data;
    }
}
