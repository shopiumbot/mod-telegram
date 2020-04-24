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
use core\modules\shop\models\Attribute;
use core\modules\shop\models\Category;
use core\modules\shop\models\Product;
use shopium\mod\telegram\components\InlineKeyboardMorePager;
use shopium\mod\telegram\components\InlineKeyboardPager;
use shopium\mod\telegram\components\KeyboardPagination;
use shopium\mod\telegram\components\SystemCommand;
use shopium\mod\cart\models\Order;
use shopium\mod\cart\models\OrderProduct;
use Longman\TelegramBot\Request;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Callback query command
 */
class CallbackqueryCommand extends SystemCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'callbackquery';
    protected $description = 'Reply to callback query';
    protected $version = '1.0.0';
    /**#@-*/

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

        // if(YII_DEBUG){
        // echo 'Callback: '.$callback_data.PHP_EOL;
        //}
        $data['callback_query_id'] = $callback_query_id;
        if ($callback_data == 'goHome') {
            return $this->telegram->executeCommand('start');
        } elseif (preg_match('/^payment\/([0-9]+)/iu', trim($callback_data), $match)) {

            $this->telegram->setCommandConfig('payment', [
                'order_id' => $match[1]
            ]);
            return $this->telegram->executeCommand('payment');

        } elseif (preg_match('/openCatalog/iu', trim($callback_data), $match)) { //preg_match('/^getCatalog\s+([0-9]+)/iu', trim($callback_data), $match)
            parse_str($callback_data, $params);
            $this->telegram->setCommandConfig('catalog', [
                'id' => $params['id']
            ]);
            return $this->telegram->executeCommand('catalog');
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


            $orderProduct = OrderProduct::findOne((int)$id);

            if ($orderProduct) {

                $keyboards[] = [
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
                ]);
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

                return Request::editMessageReplyMarkup($dataEdit);
            }
            return $this->errorMessage();

        } elseif (preg_match('/cartSpinner/iu', trim($callback_data), $match)) {
            $user_id = $callback_query->getFrom()->getId();
            parse_str($callback_data, $params);

            $orderProduct = OrderProduct::findOne([
                'order_id' => $params['order_id'],
                'product_id' => $params['product_id'],
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

            $orderProduct = OrderProduct::findOne([
                'order_id' => $params['order_id'],
                'product_id' => $params['product_id'],
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

                return $this->telegram
                    ->setCommandConfig($command, [
                        'order_id' => $params['order_id'],
                        'product_id' => $orderProduct->product_id,
                        'quantity' => $orderProduct->quantity
                    ])
                    ->executeCommand($command);
            }
            return $this->notify('Товара ранее был удален и корзины', 'info');

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

            $order = Order::find()->where(['user_id' => $user_id, 'checkout' => 0])->one();
            $quantity = 1;
            if (!$order) {
                $order = new Order;
                $order->user_id = $user_id;
                $order->firstname = $callback_query->getFrom()->getFirstName();
                $order->lastname = $callback_query->getFrom()->getLastName();
                $order->save(false);


            }


            $add = $order->addProduct($product, $quantity, $product->getFrontPrice());
            if ($add) {
                /* $data = [
                     'callback_query_id' => $callback_query_id,
                     'text' => 'Товар успешно добавлен в корзину',
                     'show_alert' => false,
                     'cache_time' => 0,
                 ];
                 $notify = Request::answerCallbackQuery($data);*/

                $data = [
                    'callback_query_id' => $callback_query_id,
                    'text' => '✅ Товар успешно добавлен в корзину',
                    'show_alert' => false,
                    'cache_time' => 0,
                ];
                $notify = Request::answerCallbackQuery($data);

                $this->telegram->setCommandConfig('catalogproductquantity', [
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'quantity' => $quantity
                ]);
                return $this->telegram->executeCommand('catalogproductquantity');
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
            ///
            /// /
            /// /
            ///
            /// /
            /// /
            /// /
            /// /
            ///

        } elseif (preg_match('/changeProductImage/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);
            print_r($params);
            die;
        } elseif (preg_match('/(productDelete|productUpdate|productSwitch)/iu', trim($callback_data), $match)) {
            parse_str($callback_data, $params);

            $data = [
                'callback_query_id' => $callback_query_id,
                'text' => 'Это демо версия!',
                // 'show_alert' => true,
                'cache_time' => 100,
            ];

            return Request::answerCallbackQuery($data);
        } elseif (preg_match('/getCatalogList/iu', trim($callback_data), $match)) { //preg_match('/^getCatalogList\/([0-9]+)/iu', trim($callback_data), $match)
            $user_id = $callback_query->getFrom()->getId();
            $order = Order::findOne(['user_id' => $user_id, 'checkout' => 0]);

            parse_str($callback_data, $params);


            if (isset($params['category_id'])) {


                $query = Product::find()->published()->sort()->applyCategories($params['category_id']);
                $pages = new KeyboardPagination([
                    'totalCount' => $query->count(),
                    // 'defaultPageSize' => 5,
                    'pageSize' => 5,
                    'currentPage' => (isset($params['page'])) ? $params['page'] : 1
                ]);

                if (isset($params['page'])) {
                    $pages->setPage($params['page']);
                    $deleleMessage = Request::deleteMessage(['chat_id' => $chat_id, 'message_id' => $update->getCallbackQuery()->getMessage()->getMessageId()]);
                } else {
                    $pages->setPage(1);
                }

                $products1 = $query->offset($pages->offset - 5)
                    ->limit($pages->limit);


                $products = $products1->all();


                $pager = new InlineKeyboardMorePager([
                    'pagination' => $pages,
                    'lastPageLabel' => false,
                    'firstPageLabel' => false,
                    'prevPageLabel' => false,
                    'maxButtonCount' => 1,
                    'internal' => false,
                    'command' => 'getCatalogList&category_id=' . $params['category_id'],
                    'nextPageLabel' => '🔄 загрузить еще...'
                ]);


                if ($products) {

                    foreach ($products as $index => $product) {
                        $keyboards = [];

                        $caption = '';
                        if ($product->hasDiscount) {
                            $caption .= '🔥🔥🔥';
                        }

                        $caption .= '*' . $product->name . '*' . PHP_EOL;
                        $caption .= $this->number_format($product->price) . ' грн' . PHP_EOL . PHP_EOL;

                        if ($product->hasDiscount) {
                            $caption .= '*🎁 Скидка*: ' . $product->discountSum . PHP_EOL . PHP_EOL;
                        }

                        if ($product->manufacturer_id) {
                            $caption .= '*Производитель*: ' . $product->manufacturer->name . PHP_EOL;
                        }
                        if ($product->sku) {
                            $caption .= '*Артикул*: ' . $product->sku . PHP_EOL;
                        }


                        $attributes = $this->attributes($product);
                        if ($attributes) {
                            $caption .= '*Характеристики:*' . PHP_EOL;
                            foreach ($attributes as $name => $data) {
                                if (!empty($data['value'])) {
                                    $caption .= '*' . $name . '*: ' . $data['value'] . ' ' . $data['abbreviation'] . PHP_EOL;
                                }
                            }
                        }
                        if ($product->description) {
                            $caption .= PHP_EOL . Html::encode($product->description) . PHP_EOL . PHP_EOL;
                        }
                        if ($order) {
                            $orderProduct = OrderProduct::findOne(['product_id' => $product->id, 'order_id' => $order->id]);
                        } else {
                            $orderProduct = null;
                        }


                        //check tarif plan
                        if (false) {
                            $images = $product->getImages();
                            print_r($images);
                            $pages2 = new KeyboardPagination([
                                'totalCount' => 3,
                                'defaultPageSize' => 1,
                                //'pageSize'=>3
                            ]);
                            $pages2->setPage(0);
                            $pagerPhotos = new InlineKeyboardPager([
                                'pagination' => $pages2,
                                'lastPageLabel' => false,
                                'firstPageLabel' => false,
                                'maxButtonCount' => 1,
                                'command' => 'changeProductImage'
                            ]);
                            if ($pagerPhotos->buttons)
                                $keyboards[] = $pagerPhotos->buttons;

                        }


                        if ($orderProduct) {
                            $keyboards[] = [
                                new InlineKeyboardButton([
                                    'text' => '—',
                                    // 'callback_data' => "spinner/{$order->id}/{$product->id}/down/catalog"
                                    'callback_data' => "query=productSpinner&order_id={$order->id}&product_id={$product->id}&type=down"
                                ]),
                                new InlineKeyboardButton([
                                    'text' => '' . $orderProduct->quantity . ' шт.',
                                    'callback_data' => time()
                                ]),
                                new InlineKeyboardButton([
                                    'text' => '+',
                                    // 'callback_data' => "spinner/{$order->id}/{$product->id}/up/catalog",
                                    'callback_data' => "query=productSpinner&order_id={$order->id}&product_id={$product->id}&type=up"
                                ]),
                                new InlineKeyboardButton([
                                    'text' => '❌',
                                    'callback_data' => "query=deleteInCart&id={$orderProduct->id}"
                                ]),
                            ];
                            //   $keyboards[] = $this->telegram->executeCommand('cartproductquantity')->getKeywords();
                        } else {


                            $keyboards[] = [
                                new InlineKeyboardButton([
                                    'text' => Yii::t('telegram/command', 'BUTTON_BUY', $this->number_format($product->getFrontPrice())),
                                    // 'callback_data' => "addCart/{$product->id}"
                                    'callback_data' => "query=addCart&product_id={$product->id}"
                                ])
                            ];
                        }

                        $keyboards[] = $this->productAdminKeywords($chat_id, $product->id);

                        //  echo Url::to($product->getImage()->getUrlToOrigin(), true) . PHP_EOL;
                        // echo $product->getImage()->getPath();

                        $imageData = $product->getImage();
                        if ($imageData) {
                            $image = $imageData->getPathToOrigin();
                        } else {
                            $image = Yii::getAlias('@uploads') . DIRECTORY_SEPARATOR . 'no-image.jpg';
                        }

                        // $image = Url::to($product->getImage()->getUrlToOrigin(), true);
                        $dataPhoto = [
                            //'photo' => Url::to($product->getImage()->getUrl('800x800'), true),
                            'photo' => $image,
                            'chat_id' => $chat_id,
                            'parse_mode' => 'Markdown',
                            'caption' => $caption,
                            'reply_markup' => new InlineKeyboard([
                                'inline_keyboard' => $keyboards
                            ]),
                        ];
                        $reqPhoto = Request::sendPhoto($dataPhoto);
                        if (!$reqPhoto->isOk()) {
                            $errorCode = $reqPhoto->getErrorCode();
                            $description = $reqPhoto->getDescription();
                            //print_r($reqPhoto);
                            $s = $this->notify("{$errorCode} {$description} " . $image, 'error');
                        }
                    }
                }


                $begin = $pages->getPage() * $pages->pageSize;


                $data['chat_id'] = $chat_id;
                if ($begin >= $pages->totalCount) {
                    $data['text'] = ' Все! ';
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

    protected $_attributes;
    public $model;
    protected $_models;

    public function attributes($product)
    {

        $eav = $product;
        /** @var \core\modules\shop\components\EavBehavior $eav */
        $this->_attributes = $eav->getEavAttributes();


        $data = [];
        foreach ($this->getModels() as $model) {
            /** @var Attribute $model */
            $abbr = ($model->abbreviation) ? ' ' . $model->abbreviation : '';


            $data[$model->title]['value'] = $model->renderValue($this->_attributes[$model->name]);
            $data[$model->title]['abbreviation'] = $abbr;
        }

        return $data;

    }

    public function getModels()
    {
        if (is_array($this->_models))
            return $this->_models;

        $this->_models = [];
        //$cr = new CDbCriteria;
        //$cr->addInCondition('t.name', array_keys($this->_attributes));

        // $query = Attribute::getDb()->cache(function () {
        $query = Attribute::find()
            ->where(['IN', 'name', array_keys($this->_attributes)])
            ->sort()
            ->all();
        // }, 3600);


        foreach ($query as $m)
            $this->_models[$m->name] = $m;

        return $this->_models;
    }


}
