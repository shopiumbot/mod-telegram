<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace shopium\mod\telegram\commands\UserCommands;


use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\InlineKeyboardPager;
use shopium\mod\telegram\components\UserCommand;
use shopium\mod\cart\models\OrderProduct;
use shopium\mod\telegram\components\KeyboardPagination;
use shopium\mod\cart\models\Order;
use Yii;

/**
 * User "/cart" command
 *
 * Display an inline keyboard with a few buttons.
 */
class CartCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'cart';

    /**
     * @var string
     */
    protected $description = 'Корзина заказа';

    /**
     * @var string
     */
    protected $usage = '/cart';

    /**
     * @var string
     */
    protected $version = '1.0';
    private $page = 0;
    protected $private_only = true;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $update = $this->getUpdate();


        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            //  $chat = $callbackQuery->getMessage()->getChat();
            //  $user = $message->getFrom();
            $chat = $message->getChat();
            $user = $callbackQuery->getFrom();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
        } else {
            $callbackQuery = null;
            $message = $this->getMessage();
            $chat = $message->getChat();
            $user = $message->getFrom();

            $chat_id = $chat->getId();
            $user_id = $user->getId();
        }
        $text = trim($message->getText(true));


        $data['chat_id'] = $chat_id;


        $order = Order::find()->where(['user_id' => $user_id, 'checkout' => 0])->one();

        if ($order) {


            if ($this->getConfig('page')) {
                $this->page = $this->getConfig('page');
            }


            $query = OrderProduct::find()->where(['order_id' => $order->id]);
            $queryCount = $query->count();
            $pages = new KeyboardPagination([
                'totalCount' => $queryCount,
                'defaultPageSize' => 1,
                //'pageSize'=>3
            ]);
            $pages->setPage($this->page);
            $products = $query->offset($pages->offset)
                ->limit($pages->limit)
                ->all();


            $pager = new InlineKeyboardPager([
                'pagination' => $pages,
                'lastPageLabel' => false,
                'firstPageLabel' => false,
                'maxButtonCount' => 1,
                'command' => 'getCart'
            ]);


            $keyboards = [];

            if ($queryCount) {
                foreach ($products as $product) {

                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => '❌',
                            'callback_data' => "cartDelete/{$product->id}"
                        ]),
                        new InlineKeyboardButton([
                            'text' => '—',
                            // 'callback_data' => "spinner/{$order->id}/{$product->product_id}/down/cart"
                            'callback_data' => "query=cartSpinner&order_id={$order->id}&product_id={$product->product_id}&page={$this->page}&type=down"
                        ]),
                        new InlineKeyboardButton([
                            'text' => $product->quantity . ' шт.',
                            'callback_data' => time()
                        ]),
                        new InlineKeyboardButton([
                            'text' => '+',
                            // 'callback_data' => "spinner/{$order->id}/{$product->product_id}/up/cart"
                            'callback_data' => "query=cartSpinner&order_id={$order->id}&product_id={$product->product_id}&page={$this->page}&type=up"
                        ])
                    ]; // 🔺 🔻

                    if ($pager->buttons)
                        $keyboards[] = $pager->buttons;

                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/command', 'BUTTON_CHECKOUT', $this->number_format($order->total_price)),
                            // 'callback_data' => "checkOut/{$order->id}"
                            'callback_data' => 'query=checkOut&id=' . $order->id

                        ]),
                    ];



                    $imageData = $product->originalProduct->getImage();

                    $text = '*Ваша корзина*' . PHP_EOL;
                    if($imageData){
                        $text .= '[' . $product->name . '](https://' . Yii::$app->request->getServerName() . ''.$imageData->getUrlToOrigin() . ')' . PHP_EOL;
                    }else{
                        $text .= '[' . $product->name . '](https://' . Yii::$app->request->getServerName() . '/uploads/no-image.jpg)' . PHP_EOL;
                    }

                   // $text .= '_описание товара_ ' . PHP_EOL;
                    $text .= '`' . $this->number_format($product->price) . ' грн / ' . $product->quantity . ' шт = ' . $this->number_format(($product->price * $product->quantity)) . ' грн`' . PHP_EOL;

                    //  $data['chat_id'] = $chat_id;
                    $data['text'] = $text;
                    $data['parse_mode'] = 'Markdown';


                    $data['reply_markup'] = new InlineKeyboard([
                        'inline_keyboard' => $keyboards
                    ]);
                    if ($callbackQuery) {

                        $data['message_id'] = $message->getMessageId();
                        $response = Request::editMessageText($data);

                        $dataReplyMarkup['reply_markup'] = new InlineKeyboard([
                            'inline_keyboard' => $keyboards
                        ]);

                        return Request::editMessageReplyMarkup(array_merge($data, $dataReplyMarkup));
                    }
                    $response = $data;

                }
            } else {
                if ($update->getCallbackQuery()) {
                    $deleleMessage = Request::deleteMessage(['chat_id' => $chat_id, 'message_id' => $update->getCallbackQuery()->getMessage()->getMessageId()]);
                }

                $data['text'] = Yii::$app->settings->get('telegram', 'empty_cart_text');
                $data['reply_markup'] = $this->startKeyboards();
                $response = $data;
            }
        } else {
            $data['text'] = Yii::$app->settings->get('telegram', 'empty_cart_text');
            $data['reply_markup'] = $this->startKeyboards();
            $response = $data;

        }

        return Request::sendMessage($response);
    }

    public function keywords()
    {
        $keyboards[] = [
            new InlineKeyboardButton(['text' => '—', 'callback_data' => 'get']),
            new InlineKeyboardButton(['text' => '2 шт.', 'callback_data' => 'get']),
            new InlineKeyboardButton(['text' => '+', 'callback_data' => 'get'])
        ];
        $keyboards[] = [
            new InlineKeyboardButton(['text' => '⬅', 'callback_data' => 'get']),
            new InlineKeyboardButton(['text' => '2 / 6', 'callback_data' => 'get']),
            new InlineKeyboardButton(['text' => '➡', 'callback_data' => 'get'])
        ];
        $keyboards[] = [
            new InlineKeyboardButton(['text' => '✅ Заказ на 130 грн. Оформить', 'callback_data' => 'get']),
        ];
        $keyboards[] = [
            new InlineKeyboardButton(['text' => '❌', 'callback_data' => 'get']),
        ];
        return $keyboards;
    }

}
