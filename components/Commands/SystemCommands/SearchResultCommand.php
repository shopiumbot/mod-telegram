<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;


use core\modules\shop\components\EavBehavior;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use core\modules\shop\models\Attribute;
use core\modules\shop\models\Product;
use shopium\mod\cart\models\OrderProductTemp;
use shopium\mod\cart\models\OrderTemp;
use shopium\mod\telegram\components\InlineKeyboardMorePager;
use shopium\mod\telegram\components\KeyboardPagination;
use shopium\mod\telegram\components\SystemCommand;
use shopium\mod\cart\models\Order;
use shopium\mod\cart\models\OrderProduct;
use Yii;
use panix\engine\Html;

/**
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class SearchResultCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'searchresult';

    /**
     * @var string
     */
    protected $description = 'Результат поиска товаров';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = true;
    public $string;
    public $page;

    protected $_attributes;
    public $model;
    protected $_models;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $update = $this->getUpdate();

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
        $chat_id = $chat->getId();
        $user_id = $user->getId();
        $this->setLanguage($user_id);
        $text = trim($message->getText(true));


        $order = OrderTemp::findOne($user_id);


        $this->string = $this->getConfig('string');

        if ($this->getConfig('page')) {
            $this->page = $this->getConfig('page');
        } else {
            $this->page = 1;
        }

        $query = Product::find()->applySearch($this->string);
        if (!in_array($user_id, $this->telegram->getAdminList())) {
            $query->published();
        }
        if (Yii::$app->settings->get('app', 'availability_hide')) {
            $query->isNotAvailability();
        }
        $query->sort();

        $pages = new KeyboardPagination([
            'totalCount' => $query->count(),
            // 'defaultPageSize' => 5,
            'pageSize' => 5,
            'currentPage' => $this->page
        ]);

        if ($this->page) {
            $pages->setPage($this->page);
            $deleleMessage = Request::deleteMessage(['chat_id' => $chat_id, 'message_id' => $message->getMessageId()]);
        } else {
            $pages->setPage(1);
        }

        $products1 = $query
            ->offset($pages->offset - 5)
            ->limit($pages->limit);

        //  var_dump($this->page);
        // echo $products1->createCommand()->rawSql.PHP_EOL;

        $products = $products1->all();


        $pager = new InlineKeyboardMorePager([
            'pagination' => $pages,
            'lastPageLabel' => false,
            'firstPageLabel' => false,
            'prevPageLabel' => false,
            'maxButtonCount' => 1,
            'internal' => false,
            'callback_data' => 'command={command}&page={page}',
            'command' => 'search&string=' . $this->string,
            'nextPageLabel' => Yii::t('telegram/default','LOAD_MORE')
        ]);


        if ($products) {


            $data['chat_id'] = $chat_id;
            $data['parse_mode'] = 'Markdown';
            $data['text'] = Yii::t('telegram/default','SEARCH_QUERY_RESULT',$this->string);
            $data['reply_markup'] = $this->startKeyboards();
            $r = Request::sendMessage($data);


            foreach ($products as $index => $product) {
                $keyboards = [];

               /* $caption = '';
                if ($product->hasDiscount) {
                    $caption .= '🔥🔥🔥';
                }

                $caption .= '<strong>' . $product->name . '</strong>' . PHP_EOL;
                $caption .= $this->number_format($product->price) . ' ' . Yii::$app->currency->active['symbol'] . PHP_EOL . PHP_EOL;

                if ($product->hasDiscount) {
                    $caption .= '<strong>🎁 Скидка</strong>: ' . $product->discountSum . PHP_EOL . PHP_EOL;
                }

                if ($product->manufacturer_id) {
                    $caption .= '<strong>Производитель</strong>: ' . $product->manufacturer->name . PHP_EOL;
                }*/
               // if ($product->sku) {
               //     $caption .= '<strong>Артикул</strong>: ' . $product->sku . PHP_EOL;
               // }


                $attributes = $this->attributes($product);
                $attributesList = [];
                if ($attributes) {
                 //   $caption .= PHP_EOL . '<strong>Характеристики:</strong>' . PHP_EOL;
                    foreach ($attributes as $name => $data) {
                        if (!empty($data['value'])) {
                            $attributesList[$name] = $data['value'];
                          //  $caption .= '<strong>' . $name . '</strong>: ' . $data['value'] . ' ' . $data['abbreviation'] . PHP_EOL;
                        }
                    }
                }
               // if ($product->description) {
               //     $caption .= PHP_EOL . Html::encode($product->description) . PHP_EOL . PHP_EOL;
               // }

                if ($order) {
                    $orderProduct = OrderProductTemp::findOne(['product_id' => $product->id, 'order_id' => $order->id]);
                } else {
                    $orderProduct = null;
                }

                if ($orderProduct) {
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => '—',
                            // 'callback_data' => "spinner/{$order->id}/{$product->id}/down/catalog"
                            'callback_data' => "query=productSpinner&order_id={$order->id}&product_id={$product->id}&type=down"
                        ]),
                        new InlineKeyboardButton([
                            'text' => '' . $orderProduct->quantity . ' '.Yii::t('shop/Product','UNIT_THING'),
                            'callback_data' => time()
                        ]),
                        new InlineKeyboardButton([
                            'text' => '+',
                            // 'callback_data' => "spinner/{$order->id}/{$product->id}/up/catalog",
                            'callback_data' => "query=productSpinner&order_id={$order->id}&product_id={$product->id}&type=up"
                        ]),
                        new InlineKeyboardButton([
                            'text' => '❌',
                            'callback_data' => "query=deleteInCart&product_id={$orderProduct->id}"
                        ]),
                    ];
                    //   $keyboards[] = $this->telegram->executeCommand('cartproductquantity')->getKeywords();
                } else {
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/default', 'BUTTON_BUY', [
                                'price' => $this->number_format($product->getFrontPrice()),
                                'currency' => Yii::$app->currency->active['symbol']
                            ]),
                            'callback_data' => "query=addCart&product_id={$product->id}"
                        ])
                    ];
                }

                $keyboards[] = $this->productAdminKeywords($chat_id, $product);


                $imageData = $product->getImage();
                if ($imageData) {
                    $image = $imageData->getPathToOrigin();
                } else {
                    $image = Yii::getAlias('@uploads') . DIRECTORY_SEPARATOR . 'no-image.jpg';
                }


                $discount = [];
                $discount['exist'] = $product->hasDiscount;
                if ($discount['exist']) {
                    $discount['sum'] = $product->discountSum;
                    $discount['end_date'] = $product->discountEndDate;
                    $discount['price'] = $product->discountPrice;
                }

                //  Request::sendMessage($test);

               // if ($user_id == 812367093) {

                    if (file_exists(Yii::getAlias('@app/web') . DIRECTORY_SEPARATOR . 'product.twig')) {
                        $tpl = '@app/web/product.twig';
                    } else {
                        $tpl = '@telegram/views/templates/product.twig';
                    }
                    $caption = Yii::$app->controller->renderPartial($tpl, [
                        'product' => [
                            'id' => $product->id,
                            'name' => Html::decode($product->name),
                            'price' => $this->number_format($product->getFrontPrice()),
                            // 'description' => ($product->description) ? Helper::Test($product->description) : false,
                            'description' => ($product->description) ? $product->description : false,
                            'sku' => ($product->sku) ? Html::decode($product->sku) : false,
                            'discount' => $discount,
                            'brand' => ($product->manufacturer_id) ? Html::decode($product->manufacturer->name) : false,
                            'category' => ($product->main_category_id) ? ($product->mainCategory) ? Html::decode($product->mainCategory->name) : false : false,
                            'availability' => $product->availability,
                            'attributes' => $attributesList,
                        ],
                        'is_admin' => ($this->telegram->isAdmin($user_id) ? true : false),
                        'currency' => [
                            'symbol' => Yii::$app->currency->active['symbol'],
                            'name' => Yii::$app->currency->active['name']
                        ]
                    ]);
                    if (!$caption) {
                        return $this->notify('Ошибка шаблона', 'error');
                    }
               // }


                //Url::to($product->getImage()->getUrlToOrigin(),true),
                $dataPhoto = [

                    'photo' => $image,
                    //'photo'=>'https://www.meme-arsenal.com/memes/50569ac974c29121ff9075e45a334942.jpg',
                    // 'photo' => Url::to($product->getImage()->getUrl('800x800'), true),
                    'chat_id' => $chat_id,
                    //'parse_mode' => 'Markdown',
                    'parse_mode' => 'HTML',
                    'caption' => $caption,
                    'reply_markup' => new InlineKeyboard([
                        'inline_keyboard' => $keyboards
                    ]),
                ];
                $reqPhoto = Request::sendPhoto($dataPhoto);
            }
        }


        $begin = $pages->getPage() * $pages->pageSize;


        $data['chat_id'] = $chat_id;
        if ($begin >= $pages->totalCount) {
            $data['text'] = Yii::t('telegram/default','PAGE_END');
        } else {
            $data['text'] = $begin . ' / ' . $pages->totalCount;
        }
        $data['disable_notification'] = false;

        if ($pager->buttons) {
            $keyboards2[] = $pager->buttons;
            $data['reply_markup'] = new InlineKeyboard([
                'inline_keyboard' => $keyboards2
            ]);
        }

        return Request::sendMessage($data);

    }


    public function attributes($product)
    {

        $eav = $product;
        /** @var EavBehavior $eav */
        $this->_attributes = $eav->getEavAttributes();


        $data = [];
        foreach ($this->getModels() as $model) {
            /** @var Attribute $model */
            $abbr = ($model->abbreviation) ? ' ' . $model->abbreviation : '';

            if (isset($this->_attributes[$model->name])) {
                $data[$model->title]['value'] = $model->renderValue($this->_attributes[$model->name]);
                $data[$model->title]['abbreviation'] = $abbr;
            }
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
            ->published()
            ->sort()
            ->all();
        // }, 3600);


        foreach ($query as $m)
            $this->_models[$m->name] = $m;

        return $this->_models;
    }
}
