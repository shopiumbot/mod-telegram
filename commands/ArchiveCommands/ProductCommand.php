<?php

namespace shopium\mod\telegram\commands\UserCommands;


use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;
use core\modules\shop\models\Product;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/product" command
 *
 * Display an inline keyboard with a few buttons.
 */
class ProductCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'product';

    /**
     * @var string
     */
    protected $description = 'get product';

    /**
     * @var string
     */
    protected $usage = '/product <id>';

    /**
     * @var string
     */
    protected $version = '1.0';
    public $enabled=false;
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

       // $telegram = new \Longman\TelegramBot\Telegram('835652742:AAEBdMpPg9TgakFa2o8eduRSkynAZxipg-c', 'pixelion');

        $preg = preg_match('/^\/product\s+([0-9]+)/iu', trim($message->getText()), $match);
        if ($preg) {
            if (isset($match[1])) {





                $product = Product::find()->published()->where(['id' => $match[1]])->one();
                if($product) {
                    $sendPhoto = Yii::$app->telegram->sendPhoto([
                        'photo' => $product->getImage()->getPathToOrigin(),
                        'chat_id' => $chat_id,
                        'parse_mode' => 'HTML',
                        'caption' => '<strong>'.$product->name.'</strong>',
                        //'reply_markup' => $inline_keyboard,
                    ]);

                    $keyboards[] = [new InlineKeyboardButton(['text' => '👉 '.$product->price . ' UAH. Купить 👈', 'callback_data' => 'callbackqueryproduct'])];
                    $keyboards[] = [new InlineKeyboardButton(['text' => 'Характеристики', 'callback_data' => 'product_attributes'])];

                    if ($this->telegram->isAdmin($chat_id)) {
                        $keyboards[] = [new InlineKeyboardButton(['text' => '✏ 📝  ⚙ Редактировать', 'callback_data' => 'get']),new InlineKeyboardButton(['text' => '❌ Удалить', 'callback_data' => 'get'])];
                        $keyboards[] = [new InlineKeyboardButton(['text' => '❓ 👤  👥 🛍 ✅ 🟢 🔴Удалить', 'callback_data' => 'get'])];
                    }


                    $data = [
                        'chat_id' => $chat_id,
                        'text' => '⬇ '.Yii::t('telegram/default','CATALOG'),
                        'reply_markup' => new InlineKeyboard([
                            'inline_keyboard' => $keyboards
                        ]),
                    ];


                }else{
                    $data = [
                        'chat_id' => $chat_id,
                        'parse_mode' => 'HTML',
                        'text' => '🚫 '. Yii::t('shop/default','NOT_FOUND_PRODUCT').' ⚠',
                        // 'reply_markup' => $inline_keyboard,
                    ];
                }
                return Request::sendMessage($data);
            }
        }




        // return Yii::$app->telegram->sendMessage($data);
    }
}
