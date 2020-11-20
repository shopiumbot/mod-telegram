<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;


use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;
use shopium\mod\cart\models\OrderProductTemp;
use shopium\mod\cart\models\OrderTemp;
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

    public function getDescription()
    {
        return Yii::t('telegram/default', 'COMMAND_CART');
    }

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


        $order = OrderTemp::findOne($user_id);


        if ($order) {


            if ($this->getConfig('page')) {
                $this->page = $this->getConfig('page');
            }


            $query = OrderProductTemp::find()->where(['order_id' => $chat_id]);
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
                // $total_price = 0;
                foreach ($products as $product) {
                    $original = $product->originalProduct;
                    if (!$original) {//todo: пересмотреть
                        $delete = $product->delete();
                        if ($delete)
                            $order->updateTotalPrice();
                    }
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => '❌',
                            'callback_data' => "cartDelete/{$product->id}"
                        ]),
                        new InlineKeyboardButton([
                            'text' => '—',
                            // 'callback_data' => "spinner/{$order->id}/{$product->product_id}/down/cart"
                            'callback_data' => "query=cartSpinner&oid={$order->id}&pid={$product->product_id}&page={$this->page}&type=down"
                        ]),
                        new InlineKeyboardButton([
                            'text' => $product->quantity . ' шт.',
                            'callback_data' => time()
                        ]),
                        new InlineKeyboardButton([
                            'text' => '+',
                            // 'callback_data' => "spinner/{$order->id}/{$product->product_id}/up/cart"
                            'callback_data' => "query=cartSpinner&oid={$order->id}&pid={$product->product_id}&page={$this->page}&type=up"
                        ])
                    ];

                    if ($pager->buttons)
                        $keyboards[] = $pager->buttons;

                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/default', 'BUTTON_CHECKOUT', [
                                'price' => $this->number_format($order->total_price),
                                'currency' => Yii::$app->currency->active['symbol']
                            ]),
                            'callback_data' => 'query=checkOut&id=' . $order->id

                        ]),
                    ];


                    $imageData = ($product->originalProduct) ? $product->originalProduct->getImage() : false;

                    $text = '*Ваша корзина*' . PHP_EOL;
                    if ($imageData) {
                        $text .= '[' . $original->name . '](https://' . Yii::$app->request->getServerName() . '' . $imageData->getUrlToOrigin() . ')' . PHP_EOL;
                    } else {
                        $text .= '[' . $original->name . '](https://' . Yii::$app->request->getServerName() . '/uploads/no-image.jpg)' . PHP_EOL;
                    }

                    // $text .= '_описание товара_ ' . PHP_EOL;
                    $text .= '`' . $this->number_format($original->price) . ' ' . Yii::$app->currency->active['symbol'] . ' / ' . $product->quantity . ' шт = ' . $this->number_format(($original->price * $product->quantity)) . ' ' . Yii::$app->currency->active['symbol'] . '`' . PHP_EOL;

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
                    //   $total_price += $original->price;

                }
            } else {
                if ($update->getCallbackQuery()) {
                    Request::deleteMessage([
                        'chat_id' => $chat_id,
                        'message_id' => $update->getCallbackQuery()->getMessage()->getMessageId()
                    ]);
                }
                $data['text'] = $this->settings->empty_cart_text;
                $data['reply_markup'] = $this->startKeyboards();
                $response = $data;

            }
        } else {
            $data['text'] = $this->settings->empty_cart_text;
            $data['reply_markup'] = $this->startKeyboards();
            $response = $data;
        }

        $result = Request::sendMessage($response);
        if ($result->isOk()) {
            $db = DB::insertMessageRequest($result->getResult());
        }
        return $result;
    }

}
