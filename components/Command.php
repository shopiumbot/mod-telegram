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
    public $user;
    public $keyword_back;
    public $keyword_cancel;
    public $keyword_admin;

    public function __construct(Api $telegram, Update $update = null)
    {
        $this->keyword_back = Yii::t('telegram/default', 'KEYWORD_BACK');
        $this->keyword_cancel = Yii::t('telegram/default', 'KEYWORD_CANCEL');
        $this->keyword_admin = Yii::t('telegram/default', 'KEYWORD_ADMIN');
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

     //   $update = $this->getUpdate();


        parent::__construct($telegram, $update);


        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {
            $user_id = $update->getCallbackQuery()->getMessage()->getFrom()->getId();
        } else {
            $user_id = $update->getMessage()->getFrom()->getId();
        }

        $this->user = \shopium\mod\telegram\models\User::findOne($user_id);
        Yii::$app->language = ($this->user->language) ? $this->user->language : 'ru';
        Yii::$app->languageManager->setActive(Yii::$app->language);
    }

    public function preExecute()
    {

        if (time() > $this->telegram->getUser()->expire) {
            $text = Yii::t('telegram/default', 'BOT_BLOCKED') . PHP_EOL;
            $text .= Yii::t('telegram/default', 'BOT_BLOCKED_REASON');
            return $this->notify($text);
        }

        return parent::preExecute();



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

        $update = $this->getUpdate();
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
        //in_array(812367093, $this->telegram->getAdminList())
        if ($this->telegram->isAdmin($chat->getId())) {
            $keyboards[] = [
                new KeyboardButton(['text' => $this->keyword_admin])
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
        $data['text'] = ($message) ? $message : Yii::t('telegram/default', 'ERROR');
        return Request::sendMessage($data);
    }

    public function notify($message = null, $type = 'info', $reply_markup = false)
    {
        if (!in_array($type, ['info', 'success', 'error', 'warning'])) {
            $type = 'info';
        }
        if ($type == 'success') {
            $preText = '*✅ ' . Yii::t('telegram/default', 'SUCCESS') . ':*' . PHP_EOL;
        } elseif ($type == 'error') {
            $preText = '*🚫 ' . Yii::t('telegram/default', 'ERROR') . ':*' . PHP_EOL;
        } elseif ($type == 'warning') {
            $preText = '*⚠ ' . Yii::t('telegram/default', 'WARNING') . ':*' . PHP_EOL;
        } else {
            $preText = '*ℹ ' . Yii::t('telegram/default', 'INFO') . ':*' . PHP_EOL;
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
        //in_array(812367093, $this->telegram->getAdminList())
        if ($this->telegram->isAdmin($this->update->getMessage()->getChat()->getId())) {
            $keyboards[] = [
                new KeyboardButton(['text' => $this->keyword_admin])
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
