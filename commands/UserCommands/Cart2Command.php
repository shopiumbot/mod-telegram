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
use shopium\mod\telegram\commands\pager\InlineKeyboardPagination;
use shopium\mod\telegram\components\UserCommand;
use shopium\mod\cart\models\OrderProduct;
use shopium\mod\cart\models\Order;
use Yii;

/**
 * User "/cart" command
 *
 * Display an inline keyboard with a few buttons.
 */
class Cart2Command extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'cart2';

    /**
     * @var string
     */
    protected $description = 'Корзина заказа';

    /**
     * @var string
     */
    protected $usage = '/cart2';

    /**
     * @var string
     */
    protected $version = '1.0';
    public $page = 1;
    public $enabled = false;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $update = $this->getUpdate();
        $res = false;
        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $chat = $message->getChat();
            $user = $message->getFrom();
            $res=true;
            print_r($callbackQuery);
        } else {
            $message = $this->getMessage();
            $chat = $message->getChat();
            $user = $message->getFrom();

        }

        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

//print_r($message->getFrom()->getId()).PHP_EOL;
        $data['chat_id'] = $chat_id;
        $order = Order::find()->where(['user_id' => $user_id, 'checkout' => 0])->one();
        if ($order) {


            $queryProducts = OrderProduct::find()->where(['order_id' => $order->id]);

            $num = 1;
            $count = $queryProducts->count();
            $total = ($count - 1) / $num + 1;

            if (empty($this->page) or $this->page < 0)
                $this->page = 1;

            if ($this->page > $total)
                $this->page = $total;

            $start = $this->page * $num - $num;


            //  $query = Product::find()->published()->sort()->applyCategories($match[1]);
           // $pages = new KeyboardPagination(['totalCount' => $queryProducts->count()]);
            $products = $queryProducts->offset($start)
                ->limit($num)
                ->all();










            $labels        = [              // optional. Change button labels (showing defaults)
                'default'  => '%d',
                //'first'    => '« %d',
                'previous' => '‹ %d',
                'current'  => '· %d ·',
                'next'     => '%d ›',
                //'last'     => '%d »',
            ];
            $items = range(1, 100);
            $selected_page = 7;
            $callback_data_format = 'command={COMMAND}&oldPage={OLD_PAGE}&newPage={NEW_PAGE}';
            $command='command_pager';
            $ikp = new InlineKeyboardPagination($items, $command);
            $ikp->setMaxButtons(5, false); // Second parameter set to always show 7 buttons if possible.
            $ikp->setLabels($labels);
            $ikp->setCallbackDataFormat($callback_data_format);

// Get pagination.
            $pagination = $ikp->getPagination($selected_page);

// or, in 2 steps.
            $ikp->setSelectedPage($selected_page);
            $pagination = $ikp->getPagination();
            if (!empty($pagination['keyboard'])) {
                //$pagination['keyboard'][0]['callback_data']; // command=testCommand&oldPage=10&newPage=1
                //$pagination['keyboard'][1]['callback_data']; // command=testCommand&oldPage=10&newPage=7

                $data['reply_markup'] = [
                    'inline_keyboard' => [
                        $pagination['keyboard'],
                    ],
                ];

            }























            //   print_r($products);
            $keyboards=[];
            foreach ($products as $product) {
                $keyboards[] = [
                    new InlineKeyboardButton(['text' => '—', 'callback_data' => "addCart/{$product->product_id}/down"]),
                    new InlineKeyboardButton(['text' => $product->quantity . ' шт.', 'callback_data' => 'get']),
                    new InlineKeyboardButton(['text' => '+', 'callback_data' => "addCart/{$product->product_id}/up"])
                ];
                $keyboards[] = [
                    new InlineKeyboardButton(['text' => '⬅' . ($this->page - 1), 'callback_data' => 'getCart/' . ($this->page - 1)]),
                    new InlineKeyboardButton(['text' => $this->page . ' / ' . $count, 'callback_data' => time()]),
                    new InlineKeyboardButton(['text' => '➡' . ($this->page + 1), 'callback_data' => 'getCart/' . ($this->page + 1)])
                ];
                $keyboards[] = [
                    new InlineKeyboardButton(['text' => '✅ Заказ на 130 грн. Оформить', 'callback_data' => 'checkOut']),
                ];
                $keyboards[] = [
                    new InlineKeyboardButton(['text' => '❌', 'callback_data' => "removeProductCart/{$product->product_id}"]),
                ];


                $text = '*' . $product->id . 'Ваша корзина*' . PHP_EOL;
                //$text .= '[Мой товар](https://images.ua.prom.st/1866772551_w640_h640_1866772551.jpg)' . PHP_EOL;
                $text .= '[Мой товар](https://images.ua.prom.st/1866772551_w640_h640_1866772551.jpg)' . PHP_EOL;
                $text .= '_описание товара_' . PHP_EOL;
                $text .= '`90 грн / 4 шт = 350 грн`' . PHP_EOL;

                $data['chat_id'] = $chat_id;
                $data['text'] = $text;
                $data['parse_mode'] = 'Markdown';
                $data['reply_markup'] = new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]);
                $response = Request::sendMessage($data);
            }

            if($res){
                $keyboards[] = [
                    new InlineKeyboardButton([
                        'text' => '🛍 Корзина',
                        'callback_data' => "getCart"
                    ])
                ];

print_r($response);
                $dataEdit['chat_id'] = $chat_id;
                $dataEdit['message_id'] = $mssage->getMessageId();
                $dataEdit['reply_markup'] = new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]);


                $response= Request::editMessageReplyMarkup($dataEdit);
            }


            /*$keyboards[] = [
                new InlineKeyboardButton(['text' => '—', 'callback_data' => "addCart/{$product->product_id}/down"]),
                new InlineKeyboardButton(['text' => $product->quantity.' шт.', 'callback_data' => 'get']),
                new InlineKeyboardButton(['text' => '+', 'callback_data' => "addCart/{$product->product_id}/up"])
            ];
            $keyboards[] = [
                new InlineKeyboardButton(['text' => '⬅', 'callback_data' => 'get']),
                new InlineKeyboardButton(['text' => '2 / 6', 'callback_data' => 'get']),
                new InlineKeyboardButton(['text' => '➡', 'callback_data' => 'get'])
            ];
            $keyboards[] = [
                new InlineKeyboardButton(['text' => '✅ Заказ на 130 грн. Офрормить', 'callback_data' => 'get']),
            ];
            $keyboards[] = [
                new InlineKeyboardButton(['text' => '❌', 'callback_data' => "removeProductCart/{$product->product_id}"]),
            ];


            $text = '*'.$product->id.'Ваша корзина*' . PHP_EOL;
            //$text .= '[Мой товар](https://images.ua.prom.st/1866772551_w640_h640_1866772551.jpg)' . PHP_EOL;
            $text .= '[Мой товар](https://yii2.pixelion.com.ua/images/get-file/2157ff033e-2.jpg)' . PHP_EOL;
            $text .= '_описание товара_' . PHP_EOL;
            $text .= '`90 грн / 4 шт = 350 грн`' . PHP_EOL;

            $data['chat_id'] = $chat_id;
            $data['text'] = $text;
            $data['parse_mode'] = 'Markdown';
            $data['reply_markup'] = new InlineKeyboard([
                'inline_keyboard' => $keyboards
            ]);
            $response = Request::sendMessage($data);*/

            foreach ($order->products as $product) {

            }

            //$response = true;
        } else {
            $data['text'] = Yii::$app->settings->get('telegram', 'empty_cart_text');
            $data['reply_markup'] = $this->startKeyboards();
            return Request::sendMessage($data);
        }

        // print_r($response);
        return $response;
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
