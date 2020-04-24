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
use core\modules\shop\models\Attribute;
use core\modules\shop\models\Product;
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
            $chat = $message->getChat();
            $user = $callbackQuery->getFrom();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
        }
        $text = trim($message->getText(true));


        $order = Order::findOne(['user_id' => $user_id, 'checkout' => 0]);


        $this->string = $this->getConfig('string');

        if ($this->getConfig('page')) {
            $this->page = $this->getConfig('page');
        } else {
            $this->page = 1;
        }

        $query = Product::find()->sort()->published();
        //->groupBy(Product::tableName() . '.`id`');
        $query->applySearch($this->string);

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
            'nextPageLabel' => '🔄 загрузить еще...'
        ]);


        if ($products) {


            $data['chat_id'] = $chat_id;
            $data['parse_mode'] = 'Markdown';
            $data['text'] = "Результат по запросу: *{$this->string}*";
            $data['reply_markup'] = $this->startKeyboards();
            $r = Request::sendMessage($data);


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
                            'callback_data' => "query=deleteInCart&product_id={$orderProduct->id}"
                        ]),
                    ];
                    //   $keyboards[] = $this->telegram->executeCommand('cartproductquantity')->getKeywords();
                } else {
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/command', 'BUTTON_BUY', $this->number_format($product->price)),
                            'callback_data' => "query=addCart&product_id={$product->id}"
                        ])
                    ];
                }

                $keyboards[] = $this->productAdminKeywords($chat_id, $product->id);


                $imageData = $product->getImage();
                if ($imageData) {
                    $image = $imageData->getPathToOrigin();
                } else {
                    $image = Yii::getAlias('@uploads') . DIRECTORY_SEPARATOR . 'no-image.jpg';
                }


                //Url::to($product->getImage()->getUrlToOrigin(),true),
                $dataPhoto = [

                    'photo' => $image,
                    //'photo'=>'https://www.meme-arsenal.com/memes/50569ac974c29121ff9075e45a334942.jpg',
                    // 'photo' => Url::to($product->getImage()->getUrl('800x800'), true),
                    'chat_id' => $chat_id,
                    'parse_mode' => 'Markdown',
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
            $data['text'] = ' Все! ';
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
