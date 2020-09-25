<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;


use core\modules\shop\models\query\ProductQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use core\modules\shop\models\Product;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use panix\engine\CMS;
use shopium\mod\cart\models\OrderProductTemp;
use shopium\mod\cart\models\OrderTemp;
use shopium\mod\telegram\components\InlineKeyboardMorePager;
use shopium\mod\telegram\components\InlineKeyboardPager;
use shopium\mod\telegram\components\KeyboardPagination;
use shopium\mod\telegram\components\SystemCommand;
use shopium\mod\cart\models\Order;
use shopium\mod\cart\models\OrderProduct;
use Longman\TelegramBot\Request;
use Yii;
use yii\helpers\Url;

/**
 * Callback query command
 */
class CallbackqueryCommand extends SystemCommand
{

    protected $name = 'callbackquery';
    protected $description = 'Reply to callback query';
    protected $version = '1.0.0';

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $update = $this->getUpdate();

        $callback_query = $update->getCallbackQuery();
        $message = $callback_query->getMessage();
        $chat_id = $message->getChat()->getId();


        // $chat = $message->getChat();
        $user = $message->getFrom();
        $user_id = $user->getId();


        $callback_query_id = $callback_query->getId();
        $callback_data = $callback_query->getData();

        $data['callback_query_id'] = $callback_query_id;
        if (preg_match('/planPay/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);
            if (isset($params['system'])) {
                return $this->telegram->setCommandConfig('plan', [
                    'system' => $params['system'],
                    'month' => $params['month'],
                ])->executeCommand('plan');
            } else {
                return $this->notify('Ошибка #planPay');
            }

        } elseif (preg_match('/sendMessage/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);
            if (isset($params['user_id'])) {
                return $this->telegram->setCommandConfig('sendMessage', [
                    'user_id' => $params['user_id']
                ])->executeCommand('sendMessage');
            }

        } elseif (preg_match('/buyOneClick/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);
            $user_id = $callback_query->getFrom()->getId();

            if (isset($params['product_id'])) {
                $product = Product::findOne($params['product_id']);
                $images = $product->getImages();
                $data = [];
                $prices = [];
                $prices[] = new LabeledPrice(['label' => 'adsasd', 'amount' => 100]);
                $inline_keyboard = new InlineKeyboard([
                    ['text' => 'Оплатить ', 'pay' => true],
                ]);
                $data['chat_id'] = $chat_id;
                $data['title'] = 'Номер заказа №';
                $data['description'] = 'Оплата заказа';
                $data['payload'] = 'order-' . CMS::gen(10);
                $data['start_parameter'] = CMS::gen(10);
                $data['provider_token'] = '632593626:TEST:i56982357197';
                $data['prices'] = $prices;
                $data['currency'] = 'UAH';
                $data['photo_url'] = Url::to($images[0]->getUrlToOrigin(), true);
                $data['photo_width'] = 200;
                $data['photo_height'] = 200;
                /*$data['need_name'] = True;
                $data['need_phone_number'] = True;
                $data['need_email'] = True;
                $data['need_shipping_address'] = True;
                $data['is_flexible'] = True;*/

                // $data['reply_markup'] = $inline_keyboard;
                $this->notify($images[0]->getUrlToOrigin());
                $pay = Request::sendInvoice($data);

                if (!$pay->getOk()) {
                    $this->notify($pay->getDescription());
                } else {
                    $this->notify('send');
                }
                return $pay;


            }


        } elseif (preg_match('/orderPay/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);
            if (isset($params['system']) && isset($params['id'])) {
                return $this->telegram->setCommandConfig('payment', [
                    'order_id' => $params['id'],
                    'system' => $params['system'],
                ])->executeCommand('payment');
            } else {
                return $this->notify('Система оплаты не настроена');
            }

        } elseif (preg_match('/openCatalog/iu', trim($callback_data), $match)) { //preg_match('/^getCatalog\s+([0-9]+)/iu', trim($callback_data), $match)
            parse_str($callback_data, $params);
            return $this->telegram->setCommandConfig('catalog', [
                'id' => (int)$params['id']
            ])->executeCommand('catalog');

        } elseif (preg_match('/getBrandsList/iu', trim($callback_data), $match)) { //preg_match('/^getCatalog\s+([0-9]+)/iu', trim($callback_data), $match)
            return $this->telegram->executeCommand('brands');
            //} elseif (preg_match('/getNewList/iu', trim($callback_data), $match)) { //preg_match('/^getCatalog\s+([0-9]+)/iu', trim($callback_data), $match)
            //    return $this->telegram->executeCommand('new');
        } elseif (preg_match('/getProduct/iu', trim($callback_data), $match)) { //preg_match('/^getCatalog\s+([0-9]+)/iu', trim($callback_data), $match)
            parse_str($callback_data, $params);
            if (isset($params['id'])) {
                $product = Product::findOne((int)$params['id']);
                $this->telegram->setCommandConfig('productitem', [
                    'product' => $product,
                    'photo_index' => 0,
                ]);
                return $this->telegram->executeCommand('productitem');
            } else {
                return $this->notify('err product item');
            }
            //} elseif (preg_match('/getNewList/iu', trim($callback_data), $match)) { //preg_match('/^getCatalog\s+([0-9]+)/iu', trim($callback_data), $match)
            //    return $this->telegram->executeCommand('new');
        } elseif (preg_match('/^cartDelete\/([0-9]+)/iu', trim($callback_data), $match)) {
            $user_id = $callback_query->getFrom()->getId();

            $this->telegram->setCommandConfig('cartproductremove', [
                'id' => $match[1],
            ]);
            return $this->telegram->executeCommand('cartproductremove');


        } elseif (preg_match('/deleteInCart/iu', trim($callback_data), $match)) { //preg_match('/^cartDeleteInCatalog\/([0-9]+)\/([0-9]+)/iu', trim($callback_data), $match)
            parse_str($callback_data, $params);

            $user_id = $callback_query->getFrom()->getId();
            $message = $callback_query->getMessage();
            $id = $params['id'];
            $photo_index = $params['photo_index'];


            $orderProduct = OrderProductTemp::findOne((int)$id);
            $product = Product::findOne($orderProduct->product_id);
            if ($orderProduct) {

                /*$keyboards[] = [
                    new InlineKeyboardButton([
                        'text' => Yii::t('telegram/command', 'BUTTON_BUY', $this->number_format($orderProduct->originalProduct->getFrontPrice())),
                        // 'callback_data' => "addCart/{$orderProduct->product_id}"
                        'callback_data' => "query=addCart&product_id={$orderProduct->product_id}"
                    ])
                ];

                $keyboards[] = $this->productAdminKeywords($chat_id, $orderProduct->product_id);

                $dataEdit['chat_id'] = $chat_id;
                $dataEdit['message_id'] = $message->getMessageId();
                $dataEdit['reply_markup'] = new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]);*/
                // return Request::editMessageReplyMarkup($dataEdit);
                $orderProduct->delete();

                if ($orderProduct->order)
                    $orderProduct->order->updateTotalPrice();

                $data = [
                    'callback_query_id' => $callback_query_id,
                    'text' => 'Товар убран из корзины',
                    'show_alert' => false,
                    'cache_time' => 0,
                ];
                $notify = Request::answerCallbackQuery($data);


                return $this->telegram
                    ->setCommandConfig('productitem', [
                        'product' => $product,
                        'photo_index' => $photo_index,
                        // 'order_id' => $order->id,
                        //'quantity' => $quantity
                    ])
                    ->executeCommand('productitem');


            }
            return $this->errorMessage();

        } elseif (preg_match('/exchangeRates/iu', trim($callback_data), $match)) {
            return $this->telegram->executeCommand('ExchangeRates');
        } elseif (preg_match('/addProduct/iu', trim($callback_data), $match)) {
            return $this->telegram->executeCommand('ProductAdd');
        } elseif (preg_match('/addAdmin/iu', trim($callback_data), $match)) {
            return $this->telegram->executeCommand('AdminAdd');
        } elseif (preg_match('/cartSpinner/iu', trim($callback_data), $match)) {
            $user_id = $callback_query->getFrom()->getId();
            parse_str($callback_data, $params);

            $orderProduct = OrderProductTemp::findOne([
                'order_id' => $params['oid'],
                'product_id' => $params['pid'],
            ]);
            if ($orderProduct) {
                if ($params['type'] == 'up') {
                    $orderProduct->quantity++;
                } else {
                    $orderProduct->quantity--;
                }
                if ($orderProduct->quantity >= 1) {
                    $orderProduct->save(false);
                }

                return $this->telegram
                    ->setCommandConfig('cart', [
                        'page' => $params['page']
                    ])
                    ->executeCommand('cart');
            }

            return $this->errorMessage();
        } elseif (preg_match('/productSpinner/iu', trim($callback_data), $match)) {


            $user_id = $callback_query->getFrom()->getId();
            parse_str($callback_data, $params);

            $orderProduct = OrderProductTemp::findOne([
                'order_id' => $params['oid'],
                'product_id' => $params['pid'],
            ]);
            if ($orderProduct) {
                if ($params['type'] == 'up') {
                    $orderProduct->quantity++;
                } else {
                    $orderProduct->quantity--;
                }
                if ($orderProduct->quantity >= 1) {
                    $orderProduct->save(false);
                }

                $command = 'catalogproductquantity';

                /*return $this->telegram
                    ->setCommandConfig($command, [
                        'order_id' => $params['order_id'],
                        'product_id' => $orderProduct->product_id,
                        'quantity' => $orderProduct->quantity
                    ])
                    ->executeCommand($command);*/


                return $this->telegram
                    ->setCommandConfig('productitem', [
                        'product' => $orderProduct->originalProduct,
                        'photo_index' => (isset($params['img'])) ? $params['img'] : 0,
                        // 'order_id' => $order->id,
                        //'quantity' => $quantity
                    ])
                    ->executeCommand('productitem');
            }
            return $this->notify('Товара ранее был удален из корзины', 'info');

        } elseif (preg_match('/checkOut/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);

            if (isset($params['id'])) {
                return $this->telegram->setCommandConfig('checkout', [
                    'order_id' => $params['id'],
                ])->executeCommand('checkout');
            }

            return $this->errorMessage();


        } elseif (preg_match('/addCart/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);
            $user_id = $callback_query->getFrom()->getId();
            $product_id = $params['product_id'];


            $product = Product::findOne($product_id);
            if ($product) {
                $order = OrderTemp::findOne($user_id);
                $quantity = 1;
                if (!$order) {
                    $order = new OrderTemp;
                    $order->id = $user_id;
                    //$order->firstname = $callback_query->getFrom()->getFirstName();
                    //$order->lastname = $callback_query->getFrom()->getLastName();
                    $order->save(false);
                }


                $add = $order->addProduct($product, $quantity);
                if ($add) {

                    $data = [
                        'callback_query_id' => $callback_query_id,
                        'text' => "✅ Товар {$product->name} успешно добавлен в корзину",
                        'show_alert' => false,
                        'cache_time' => 0,
                    ];
                    $notify = Request::answerCallbackQuery($data);


                    return $this->telegram
                        ->setCommandConfig('productitem', [
                            'product' => $product,
                            'photo_index' => (isset($params['photo_index'])) ? $params['photo_index'] : 0,
                            // 'order_id' => $order->id,
                            //'quantity' => $quantity
                        ])
                        ->executeCommand('productitem');

                    /*$this->telegram->setCommandConfig('catalogproductquantity', [
                        'product_id' => $product->id,
                        'order_id' => $order->id,
                        'quantity' => $quantity
                    ]);
                    return $this->telegram->executeCommand('catalogproductquantity');*/
                }
            } else {

                $data = [
                    'callback_query_id' => $callback_query_id,
                    'text' => "🙁 На данный момент товара не существует",
                    'show_alert' => false,
                    'cache_time' => 0,
                ];
                return Request::answerCallbackQuery($data);
            }
            return Request::emptyResponse();


        } elseif (preg_match('/^addCart2\/([0-9]+)/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);
            $user_id = $callback_query->getFrom()->getId();

            $product = Product::findOne($match[1]);
            $order = Order::find()->where(['user_id' => $user_id, 'checkout' => 0])->one();
            $quantity = 1;
            if (!$order) {
                $order = new Order;
                $order->user_id = $user_id;
                $order->firstname = $callback_query->getFrom()->getFirstName();
                $order->lastname = $callback_query->getFrom()->getLastName();
                $order->save();

                $order->addProduct($product, $quantity, $product->price);
            } else {
                $op = OrderProduct::find()->where(['product_id' => $product->id, 'order_id' => $order->id])->one();
                if ($op) {
                    // $op->quantity++;
                    // $quantity = $op->quantity;
                    //  $op->save(false);
                    //$op->delete();
                } else {
                    $order->addProduct($product, $quantity, $product->price);
                }
            }

            $this->telegram->setCommandConfig('catalogproductquantity', [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'quantity' => $quantity
            ]);
            $response = $this->telegram->executeCommand('catalogproductquantity');

            return $response;

        } elseif (preg_match('/getCart/', trim($callback_data), $match)) { //preg_match('/^getCart\/([0-9]+)/iu', trim($callback_data), $match)

            $params = InlineKeyboardPager::getParametersFromCallbackData($callback_data);

            if (isset($params['page'])) {
                $this->telegram->setCommandConfig('cart', [
                    'page' => $params['page'],
                ]);
            }
            $response = $this->telegram->executeCommand('cart');

            return $response;

        } elseif (preg_match('/search/', trim($callback_data), $match)) {
            $user_id = $callback_query->getFrom()->getId();
            parse_str($callback_data, $params);


            $config['string'] = $params['string'];
            $config['page'] = (isset($params['page'])) ? $params['page'] : 1;
            if (isset($params['string'])) {

                return $this->telegram
                    ->setCommandConfig('searchresult', $config)
                    ->executeCommand('searchresult');
            }

            return Request::emptyResponse();

        } elseif (preg_match('/getHistory/', trim($callback_data), $match)) {

            $params = InlineKeyboardPager::getParametersFromCallbackData($callback_data);

            if (isset($params['page'])) {
                $this->telegram->setCommandConfig('history', [
                    'page' => $params['page'],
                ]);
            }
            $response = $this->telegram->executeCommand('history');

            return $response;

        } elseif (preg_match('/changeProductImage/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);
            $user_id = $callback_query->getFrom()->getId();
            $message = $callback_query->getMessage();
            $order = OrderTemp::findOne($user_id);
            // print_r($params);
            $product_id = $params['product_id'];
            $page = $params['page'];
            $product = Product::findOne($product_id);


            $this->telegram->setCommandConfig('productitem', [
                'photo_index' => $page,
                'product' => $product
            ])
                ->executeCommand('productitem');

        } elseif (preg_match('/productSwitch/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);

            $this->telegram
                ->setCommandConfig('productswitch', [
                    'id' => $params['id'],
                    'switch' => $params['switch']
                ])
                ->executeCommand('productswitch');
            return Request::emptyResponse();


        } elseif (preg_match('/(productDelete)/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);

            return $this->telegram
                ->setCommandConfig('productremove', [
                    'id' => $params['id']
                ])
                ->executeCommand('productremove');


        } elseif (preg_match('/(productUpdate)/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);

            $data = [
                'callback_query_id' => $callback_query_id,
                'text' => 'В разработке!',
                // 'show_alert' => true,
                'cache_time' => 5000,
            ];

            return Request::answerCallbackQuery($data);

        } elseif (preg_match('/getList/iu', trim($callback_data), $match)) {
            $user_id = $callback_query->getFrom()->getId();
            parse_str($callback_data, $params);


            /** @var Product|ProductQuery $query */
            $query = Product::find()->sort();

            if (Yii::$app->settings->get('app', 'availability_hide')) {
                $query->isNotAvailability();
            }

            if ($params['model'] == 'brands') {
                if (isset($params['id'])) {
                    $pagerCommand = 'getList&model=brands&id=' . $params['id'];
                    $query->applyManufacturers($params['id']);
                }
            } elseif ($params['model'] == 'catalog') {
                if (isset($params['id'])) {
                    $pagerCommand = 'getList&model=catalog&id=' . $params['id'];
                    $query->applyCategories($params['id']);
                }
            } elseif ($params['model'] == 'new') {
                $pagerCommand = 'getList&model=new';
                if (isset($this->settings->label_expire_new) && $this->settings->label_expire_new) {
                    $query->int2between(time(), time() - (86400 * $this->settings->label_expire_new));
                } else {
                    $query->int2between(-1, -1);
                }
            }


            if (!in_array($user_id, $this->telegram->getAdminList())) {
                $query->published();
            }

            $pages = new KeyboardPagination([
                'totalCount' => $query->count(),
                // 'defaultPageSize' => 5,
                'pageSize' => $this->settings->pagenum_telegram,
                'currentPage' => (isset($params['page'])) ? (int)$params['page'] : 1
            ]);

            if (isset($params['page'])) {
                $pages->setPage($params['page']);
                $deleleMessage = Request::deleteMessage([
                    'chat_id' => $chat_id,
                    'message_id' => $update->getCallbackQuery()->getMessage()->getMessageId()
                ]);
            } else {
                $pages->setPage(1);
            }

            if ((int)$this->settings->pagenum_telegram > 1) {
                $offset = $pages->offset - (int)$this->settings->pagenum_telegram;
            } else {
                $offset = 0;
            }
            $query->offset($offset)->limit($pages->limit);

            $products = $query->all();

            $pager = new InlineKeyboardMorePager([
                'pagination' => $pages,
                'lastPageLabel' => false,
                'firstPageLabel' => false,
                'prevPageLabel' => false,
                'maxButtonCount' => 1,
                'internal' => false,
                'command' => $pagerCommand,
                'nextPageLabel' => '🔄 загрузить еще...'
            ]);


            if ($products) {
                foreach ($products as $index => $product) {
                    $s = $this->telegram->setCommandConfig('productitem', [
                        'photo_index' => (isset($params['photo_index'])) ? $params['photo_index'] : 0,
                        'product' => $product,
                        // 'test' => new \stdClass()
                    ])->executeCommand('productitem');
                }

            } else {
                $data = [
                    'callback_query_id' => $callback_query_id,
                    'text' => 'Товаров нет',
                    //'show_alert' => true,
                    'cache_time' => 100,
                ];
                return Request::answerCallbackQuery($data);
            }

            $begin = $pages->getPage() * $pages->pageSize;

            $data['chat_id'] = $chat_id;
            $data['parse_mode'] = 'Markdown';
            if ($begin >= $pages->totalCount) {
                $data['text'] = 'Все!';
            } else {
                $data['text'] = 'показано: *' . $begin . '* из *' . $pages->totalCount . '*';
            }
            $data['disable_notification'] = false;
            //todo добавить кнопку вернуться в каталог или еще чтото, после "Все!"
            if ($pager->buttons) {
                $keyboards2[] = $pager->buttons;
                $data['reply_markup'] = new InlineKeyboard([
                    'inline_keyboard' => $keyboards2
                ]);
            }
            return Request::sendMessage($data);

            // return Request::emptyResponse();

        } elseif (preg_match('/getBrandsList123123/iu', trim($callback_data), $match)) {
            $user_id = $callback_query->getFrom()->getId();
            $order = Order::findOne(['user_id' => $user_id, 'checkout' => 0]);

            parse_str($callback_data, $params);

            if (isset($params['id'])) {

                /** @var Product|\yii\db\ActiveQuery $query */

                $pagerCommand = 'getBrandsList&id=' . $params['id'];
                $query = Product::find()->sort()->applyManufacturers($params['id']);

                if (!in_array($user_id, $this->telegram->getAdminList())) {
                    $query->published();
                }

                $pages = new KeyboardPagination([
                    'totalCount' => $query->count(),
                    // 'defaultPageSize' => 5,
                    'pageSize' => $this->settings->pagenum_telegram,
                    'currentPage' => (isset($params['page'])) ? (int)$params['page'] : 1
                ]);

                if (isset($params['page'])) {
                    $pages->setPage($params['page']);
                    $deleleMessage = Request::deleteMessage([
                        'chat_id' => $chat_id,
                        'message_id' => $update->getCallbackQuery()->getMessage()->getMessageId()
                    ]);
                } else {
                    $pages->setPage(1);
                }

                if ((int)$this->settings->pagenum_telegram > 1) {
                    $offset = $pages->offset - (int)$this->settings->pagenum_telegram;
                } else {
                    $offset = 0;
                }
                $query->offset($offset)->limit($pages->limit);

                $products = $query->all();

                $pager = new InlineKeyboardMorePager([
                    'pagination' => $pages,
                    'lastPageLabel' => false,
                    'firstPageLabel' => false,
                    'prevPageLabel' => false,
                    'maxButtonCount' => 1,
                    'internal' => false,
                    'command' => $pagerCommand,
                    'nextPageLabel' => '🔄 загрузить еще...'
                ]);


                if ($products) {
                    foreach ($products as $index => $product) {
                        $s = $this->telegram->setCommandConfig('productitem', [
                            'photo_index' => (isset($params['photo_index'])) ? $params['photo_index'] : 0,
                            'product' => $product
                        ])
                            ->executeCommand('productitem');
                    }
                } else {
                    $data = [
                        'callback_query_id' => $callback_query_id,
                        'text' => 'Товаров нет',
                        //'show_alert' => true,
                        'cache_time' => 100,
                    ];
                    return Request::answerCallbackQuery($data);
                }

                $begin = $pages->getPage() * $pages->pageSize;

                $data['chat_id'] = $chat_id;
                if ($begin >= $pages->totalCount) {
                    $data['text'] = 'Все!';
                } else {
                    $data['text'] = $begin . ' / ' . $pages->totalCount;
                }
                $data['disable_notification'] = false;
                //todo добавить кнопку вернуться в каталог или еще чтото, после "Все!"
                if ($pager->buttons) {
                    $keyboards2[] = $pager->buttons;
                    $data['reply_markup'] = new InlineKeyboard([
                        'inline_keyboard' => $keyboards2
                    ]);
                }
                return Request::sendMessage($data);
            }
            return Request::emptyResponse();
        } else {
            $text = ' Hello World!';
        }
        /*  $data = [
              'callback_query_id' => $callback_query_id,
              'text'              => $text,
              'show_alert'        => $callback_data === 'thumb up',
              'cache_time'        => 5,
          ];*/

        return Request::answerCallbackQuery($data);

    }

}
