<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;


use core\modules\shop\models\Manufacturer;
use core\modules\shop\models\Product;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use core\modules\shop\models\Category;
use shopium\mod\telegram\components\UserCommand;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * User "/catalog" command
 *
 * Display an inline keyboard with a few buttons.
 */
class CatalogCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'catalog';

    /**
     * @var string
     */
    protected $description = 'Каталог продукции';

    /**
     * @var string
     */
    protected $usage = '/catalog';

    /**
     * @var string
     */
    protected $version = '1.1';

    /**
     * @var string
     */
    public $id;
    public $private_only = false;

    public function getDescription(): string
    {
        return Yii::t('telegram/default', 'CATALOG');
    }

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $update = $this->getUpdate();

        $isCallback = false;
        if ($update->getCallbackQuery()) {
            $isCallback = true;
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
        if (($this->id = trim($this->getConfig('id'))) === '') {
            $this->id = 1;
        }

        $root = Category::findOne($this->id);
        if ($root) {
            $categories = $root->children()->published()->all();


            $keyboards = [];
            $keyboardsFirst = [];

            if ($categories) {
                foreach ($categories as $category) {

//$s=$category->products;
                    //$count = $s->isNotAvailability()->count();

                    /*$countQuery = Product::find()->where(['main_category_id' => $category->id])->published();
                    if (Yii::$app->settings->get('app', 'availability_hide')) {
                        $countQuery->isNotAvailability();
                    }

                    $count = $countQuery->count();*/
                    //  $count = $category->countItems();


                    $icon = ($category->icon) ? $category->icon . ' ' : '';
                    $childCount = $category->children()->published()->count();
                    $name = (!empty($category->name)) ? $category->name : '' . Yii::t('telegram/default', 'NO_TRANSLATE') . ' ' . Yii::$app->languageManager->active['icon'];
                    if ($childCount) {
                        $keyboards[] = new InlineKeyboardButton([
                            'text' => $icon . $name,
                            'callback_data' => 'query=openCatalog&id=' . $category->id
                        ]);

                    } else {

                        $count = $category->countByAvailabilityItems;
                        if ($count) {
                            $keyboards[] = new InlineKeyboardButton([
                                'text' => $icon . $name . ' (' . $count . ')',
                                'callback_data' => 'query=getList&model=catalog&id=' . $category->id
                            ]);
                        }

                    }

                }

                $keyboards = array_chunk($keyboards, $root->chunk);
            } else {
                return $this->notify(Yii::t('telegram/default', 'CATALOG_NO_ITEMS'), 'info');
            }
        } else {
            return $this->notify(Yii::t('telegram/default', 'CATALOG_NO_ITEMS'), 'info');
        }

        if ($isCallback) {

            $back = $root->parent()->one();

            if ($back) {
                $keyboards[] = [new InlineKeyboardButton([
                    'text' => '↩ ' . $back->name,
                    'callback_data' => 'query=openCatalog&id=' . $back->id
                ])];
            } else {
                if (isset($this->settings->enable_brands) && $this->settings->enable_brands) {
                    $brands = Manufacturer::find()->published()->count();
                    if ($brands) {
                        $keyboards[] = ArrayHelper::merge($keyboards, [
                            new InlineKeyboardButton([
                                'text' => Yii::t('telegram/default', 'BRANDS', $brands),
                                'callback_data' => 'query=getBrandsList'
                            ])
                        ]);
                    }
                }
                if (isset($this->settings->enable_discounts) && $this->settings->enable_discounts) {
                    $query = Product::find()->published();
                    $query = $this->getDiscountQuery($query);
                    $countDiscount = $query->count();
                    if ($countDiscount) {
                        $keyboardsFirst[] = [
                            new InlineKeyboardButton([
                                'text' => Yii::t('telegram/default', 'DISCOUNT', $countDiscount),
                                'callback_data' => 'query=getList&model=discounts'
                            ])
                        ];
                    }
                }
                if (isset($this->settings->enable_new) && $this->settings->enable_new) {
                    $queryNew = Product::find()->published();
                    $queryNew = $this->getNewQuery($queryNew);
                    $countNew = $queryNew->count();

                    if ($countNew) {
                        $keyboardsFirst[] = [
                            new InlineKeyboardButton([
                                'text' => Yii::t('telegram/default', 'NEW', $countNew),
                                'callback_data' => 'query=getList&model=new'
                            ])
                        ];
                    }
                }

                $keyboards = ArrayHelper::merge($keyboardsFirst, $keyboards);


            }

            if ($keyboards) {
                $dataEdit['chat_id'] = $chat_id;
                $dataEdit['message_id'] = $message->getMessageId();
                $dataEdit['reply_markup'] = new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]);
                $request = Request::editMessageReplyMarkup($dataEdit);
                if (!$request->isOk()) {
                    return $this->notify($chat_id . ' editcatalog:' . $message->getMessageId() . ': ' . $request->getDescription(), 'error');
                }
                return $request;
            } else {
                return $this->notify('non-keywords', 'error');
            }

        } else {

            if (isset($this->settings->enable_brands) && $this->settings->enable_brands) {
                $brands = Manufacturer::find()->published()->count();
                if ($brands) {
                    $keyboards[] = ArrayHelper::merge($keyboards, [new InlineKeyboardButton([
                        'text' => Yii::t('telegram/default', 'BRANDS', $brands),
                        'callback_data' => 'query=getBrandsList'
                    ])]);
                }
            }
            if (isset($this->settings->enable_discounts) && $this->settings->enable_discounts) {
                $query = Product::find()->published();
                $query = $this->getDiscountQuery($query);
                $countDiscount = $query->count();
                if ($countDiscount) {
                    $keyboardsFirst[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/default', 'DISCOUNT', $countDiscount),
                            'callback_data' => 'query=getList&model=discounts'
                        ])
                    ];
                }
            }

            if (isset($this->settings->enable_new) && $this->settings->enable_new) {
                $queryNew = Product::find()->published();
                $queryNew = $this->getNewQuery($queryNew);
                $countNew = $queryNew->count();
                if ($countNew) {
                    $keyboardsFirst[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/default', 'NEW', $countNew),
                            'callback_data' => 'query=getList&model=new'
                        ])
                    ];
                }
            }
            $keyboards = ArrayHelper::merge($keyboardsFirst, $keyboards);

            $data = [
                'chat_id' => $chat_id,
                'text' => Yii::t('telegram/default', 'CHOOSE_SECTION'),
                'reply_markup' => new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]),
            ];


            $dataCatalog['text'] = Yii::t('telegram/default', 'CATALOG');
            $dataCatalog['chat_id'] = $chat_id;
            $dataCatalog['reply_markup'] = $this->catalogKeyboards();
            $buttonsResponse = Request::sendMessage($dataCatalog);

            if ($buttonsResponse->isOk()) {
                $db = DB::insertMessageRequest($buttonsResponse->getResult());
            }
            $result = $data;

        }
        $response = Request::sendMessage($result);
        if ($response->isOk()) {
            $db = DB::insertMessageRequest($response->getResult());
        }


        return $response;
    }


}
