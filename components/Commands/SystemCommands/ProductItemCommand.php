<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use core\modules\images\models\Image;
use core\modules\shop\models\Attribute;
use core\modules\shop\models\Product;
use League\HTMLToMarkdown\HtmlConverter;
use Longman\TelegramBot\Entities\ServerResponse;
use Mpdf\Tag\P;
use shopium\mod\cart\models\OrderProductTemp;
use shopium\mod\cart\models\OrderTemp;
use shopium\mod\telegram\components\Helper;
use shopium\mod\telegram\components\InlineKeyboardPager;
use shopium\mod\telegram\components\KeyboardPagination;
use shopium\mod\telegram\components\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InputMedia\InputMediaPhoto;
use Longman\TelegramBot\Request;
use shopium\mod\cart\models\Order;
use shopium\mod\cart\models\OrderProduct;
use panix\engine\Html;
use Yii;
use yii\base\Exception;


class ProductItemCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'productitem';

    /**
     * @var string
     */
    protected $description = 'productitem';

    /**
     * @var string
     */
    protected $version = '1.0.0';


    /**
     * @var bool
     */
    protected $private_only = true;
    public $photo_index = 0;
    public $product;

    public function execute(): ServerResponse
    {

        $update = $this->getUpdate();


        if (($this->photo_index = $this->getConfig('photo_index')) === '') {
            $this->photo_index = 0;
        }

        $this->product = $this->getConfig('product');
        $callbackData = false;
        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $user = $callbackQuery->getFrom();
            parse_str($callbackQuery->getData(), $params);
            if (isset($params['command'])) {
                if ($params['command'] == 'changeProductImage') {
                    $callbackData = 'changeProductImage';
                }
            }
            if (isset($params['query'])) {
                if ($params['query'] == 'addCart') {
                    $callbackData = $params['query'];
                } elseif ($params['query'] == 'deleteInCart') {
                    $callbackData = $params['query'];
                } elseif ($params['query'] == 'productSpinner') {
                    $callbackData = $params['query'];
                }
            }

        } else {
            $message = $this->getMessage();
            $user = $message->getFrom();
        }
        $chat = $message->getChat();
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $this->setLanguage($user_id);

        $keyboards = [];

        $order = OrderTemp::findOne($user_id);
        /** @var Product $product */
        $product = $this->product;

        $caption = '';
        if ($product->hasDiscount) {
            $caption .= '🔥🔥🔥';
        }

        $caption .= '<strong>' . $product->name . '</strong> ' . ((!$product->switch) ? '<code>(наименование скрыто)</code>' : '') . ' ' . PHP_EOL;
        $caption .= $this->number_format($product->price) . ' ' . Yii::$app->currency->active['symbol'] . PHP_EOL . PHP_EOL;

        if ($product->hasDiscount) {
            $caption .= '<strong>🎁 Скидка</strong>: ' . $product->discountSum . PHP_EOL . PHP_EOL;
        }
        if ($product->availability != Product::AVAILABILITY_YES) {
            $caption .= '<strong>Наличие:</strong> ' . Product::t('AVAILABILITY_' . $product->availability) . '' . PHP_EOL . PHP_EOL;
        }
        if ($product->manufacturer_id) {
            $caption .= '<strong>Производитель</strong>: ' . $product->manufacturer->name . PHP_EOL;
        }
        if ($product->sku) {
            $caption .= '<strong>Артикул</strong>: ' . $product->sku . PHP_EOL;
        }


        $attributes = $this->attributes($product);
        $attributesList = [];
        if ($attributes) {
            $caption .= PHP_EOL . '<strong>Характеристики:</strong>' . PHP_EOL;
            foreach ($attributes as $name => $data) {
                if (!empty($data['value'])) {
                    $attributesList[$name] = $data['value'];
                    $caption .= '<strong>' . $name . '</strong>: ' . $data['value'] . ' ' . $data['abbreviation'] . PHP_EOL;
                }
            }
        }


        if ($product->description) {
            $caption .= PHP_EOL . 'Описание:' . PHP_EOL;
            // $caption .= Helper::Test($product->description) . PHP_EOL . PHP_EOL;
            $caption .= $product->description . PHP_EOL . PHP_EOL;
        }
        if ($order) {
            $orderProduct = OrderProductTemp::findOne(['product_id' => $product->id, 'order_id' => $order->id]);
        } else {
            $orderProduct = null;
        }


        //check tarif plan
        $images = $product->getImages();
        if (true) {


            $pages2 = new KeyboardPagination([
                'totalCount' => count($images),
                'defaultPageSize' => 1,
                //'pageSize'=>3
            ]);
            $pages2->setPage($this->photo_index);
            $pagerPhotos = new InlineKeyboardPager([
                'pagination' => $pages2,
                'lastPageLabel' => false,
                'firstPageLabel' => false,
                'maxButtonCount' => 1,
                'command' => 'changeProductImage&product_id=' . $product->id
                //'command' => 'getCatalogList&change=1',
                //'callback_data'=>'command={command}&photo_index={page}'
            ]);
            if ($pagerPhotos->buttons)
                $keyboards[] = $pagerPhotos->buttons;


        }

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            $keyboards[] = [
                new InlineKeyboardButton([
                    'text' => 'Чтобы купить этот товар перейдите в бота',
                    //'url' => "https://t.me/shopiumbot?startgroup=test",
                    'url' => "tg://resolve?domain=shopiumbot"
                ]),
            ];
        } else {
            if ($orderProduct) {
                $keyboards[] = [
                    new InlineKeyboardButton([
                        //'text' => "{$order->id}&product_id={$product->id}",
                        'text' => '—',
                        // 'callback_data' => time()
                        'callback_data' => "query=productSpinner&oid={$order->id}&pid={$product->id}&type=down&img={$this->photo_index}"
                    ]),
                    new InlineKeyboardButton([
                        'text' => $orderProduct->quantity . ' '.Yii::t('shop/Product','UNIT_THING'),
                        'callback_data' => time()
                    ]),
                    new InlineKeyboardButton([
                        'text' => '+',
                        'callback_data' => "query=productSpinner&oid={$order->id}&pid={$product->id}&type=up&img={$this->photo_index}"
                    ]),
                    new InlineKeyboardButton([
                        'text' => '❌',
                        'callback_data' => "query=deleteInCart&id={$orderProduct->id}&photo_index={$this->photo_index}"
                    ]),
                ];
            } else {
                if ($product->availability != Product::AVAILABILITY_NOT) {
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/default', 'BUTTON_BUY', [
                                'price' => $this->number_format($product->getFrontPrice()),
                                'currency' => Yii::$app->currency->active['symbol']
                            ]),
                            'callback_data' => "query=addCart&product_id={$product->id}&photo_index={$this->photo_index}"
                        ])
                    ];
                }
            }
            /*$keyboards[] = [
                new InlineKeyboardButton([
                    'text' => 'Купить в один клик',
                    'callback_data' => "query=buyOneClick&product_id={$product->id}"
                ])
            ];*/
            $keyboards[] = $this->productAdminKeywords($chat_id, $product);
        }
        /** @var Image $imageData */
        $image = Yii::getAlias('@uploads') . DIRECTORY_SEPARATOR . 'no-image.jpg';
        if ($images) {
            $imageData = $images[$this->photo_index];
            if ($imageData) {
                if ($imageData->telegram_file_id) {
                    list($bot_id, $file_id) = explode(':', $imageData->telegram_file_id);
                    if ($file_id == $this->getTelegram()->getBotId()) {
                        //todo check to bots ids


                    }
                    $image = $file_id;
                } else {
                    //if ($this->settings->watermark_enable) {
                    //    $image = $imageData->getUrl(false,true);
                    //}else{
                    $image = $imageData->getPathToOrigin();
                    // }


                }

            }
        }

        $test = [
            // 'text' => json_encode($images),
            'chat_id' => $chat_id,
        ];


        $discount = [];
        $discount['exist'] = $product->hasDiscount;
        if ($discount['exist']) {
            $discount['sum'] = $product->discountSum;
            $discount['end_date'] = $product->discountEndDate;
            $discount['price'] = $product->discountPrice;
        }

        //  Request::sendMessage($test);

        //if ($user_id == 812367093) {

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
                'description' => ($product->description) ? preg_replace("/<br\s*\/?>\s*/i", PHP_EOL, $product->description) : false,
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
        if ($callbackData == 'changeProductImage') {

            $dataMedia = [
                'chat_id' => $user_id,
                'message_id' => $message->getMessageId(),
                'media' => new InputMediaPhoto([
                    'media' => $image
                ]),
            ];

            $dataCaption = [
                'chat_id' => $user_id,
                'message_id' => $message->getMessageId(),
                'caption' => preg_replace("/\n+/", "\n", $caption),
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ])
            ];

            $reqMedia = Request::editMessageMedia($dataMedia);
            if ($reqMedia->isOk()) {
                if (isset($imageData)) {
                    if (!$imageData->telegram_file_id) {
                        //todo: добавить проверку на бота, ИД или токен, еще не ясно

                        $imageData->telegram_file_id = $this->getTelegram()->getBotId() . ':' . $reqMedia->getResult()->photo[0]['file_id'];
                        $imageData->save(false);
                    }
                }
                //$this->notify(json_encode($reqMedia->getResult()), 'error');
            } else {
                $errorCode = $reqMedia->getErrorCode();
                $description = $reqMedia->getDescription();
                $this->notify("{$errorCode} {$description} " . $image, 'error');

            }
            $reqCaption = Request::editMessageCaption($dataCaption);

            if (!$reqCaption->isOk()) {
                $errorCode = $reqCaption->getErrorCode();
                $description = $reqCaption->getDescription();
                $this->notify("{$errorCode} {$description} " . $image, 'error');
            }

            return $reqCaption;

        } elseif ($callbackData == 'deleteInCart' || $callbackData == 'addCart' || $callbackData == 'productSpinner') {
            $dataEdit['chat_id'] = $chat_id;
            $dataEdit['message_id'] = $message->getMessageId();
            $dataEdit['reply_markup'] = new InlineKeyboard([
                'inline_keyboard' => $keyboards
            ]);
            return Request::editMessageReplyMarkup($dataEdit);
        } else {
            // $image = Url::to($product->getImage()->getUrlToOrigin(), true);

            if($images){
                $data = [
                    //'photo' => Url::to($product->getImage()->getUrl('800x800'), true),
                    'photo' => $image,
                    'chat_id' => $chat_id,
                    'parse_mode' => 'HTML',
                    'caption' => $caption,
                    'reply_markup' => new InlineKeyboard([
                        'inline_keyboard' => $keyboards
                    ]),
                ];

                $request = Request::sendPhoto($data);
            }else{
                $data = [
                    //'photo' => Url::to($product->getImage()->getUrl('800x800'), true),
                    //'photo' => $image,
                    'chat_id' => $chat_id,
                    'parse_mode' => 'HTML',
                    'text' => $caption,
                    'reply_markup' => new InlineKeyboard([
                        'inline_keyboard' => $keyboards
                    ]),
                ];

                $request = Request::sendMessage($data);
            }

            if ($request->isOk()) {

                if (isset($imageData)) {
                    if (!$imageData->telegram_file_id) {
                        $imageData->telegram_file_id = $this->getTelegram()->getBotId() . ':' . $request->getResult()->photo[0]['file_id'];
                        $imageData->save(false);
                    }
                }


            } else {
                $errorCode = $request->getErrorCode();
                $description = $request->getDescription();
                //print_r($reqPhoto);
                $s = $this->notify("sendPhoto: {$errorCode} {$description} " . $image, 'error');
            }

            //
        }


        return Request::emptyResponse();
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
