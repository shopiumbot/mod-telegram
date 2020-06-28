<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;


use core\modules\shop\models\Manufacturer;
use core\modules\shop\models\Product;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;
use core\modules\shop\models\Category;
use shopium\mod\telegram\components\UserCommand;
use Yii;
use yii\helpers\ArrayHelper;

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

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
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

        if (($this->id = trim($this->getConfig('id'))) === '') {
            $this->id = 1;
        }

        $root = Category::findOne($this->id);
        $categories = $root->children()->all();


        $keyboards = [];
        $keyboardsFirst = [];

        if ($categories) {
            foreach ($categories as $category) {
                $count = $category->countItems;
                $icon = ($category->icon) ? $category->icon . ' ' : '';
                $child = $category->children()->count();

                if ($child) {

                    $keyboards[] = new InlineKeyboardButton([
                        'text' => $icon . $category->name,
                        'callback_data' => 'query=openCatalog&id=' . $category->id
                    ]);

                } else {
                    if ($count) {
                        $keyboards[] = new InlineKeyboardButton([
                            'text' => $icon . $category->name . ' (' . $count . ')',
                            'callback_data' => 'query=getList&model=catalog&id=' . $category->id
                        ]);
                    }

                }

            }
            $keyboards = array_chunk($keyboards, $root->chunk);
        } else {
            return $this->notify('В каталоге нет продукции', 'info');
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
                        $keyboards[] = ArrayHelper::merge($keyboards, [new InlineKeyboardButton([
                            'text' => "💎️ Бренды ({$brands})",
                            'callback_data' => 'query=getBrandsList'
                        ])]);
                    }
                }
                if (isset($this->settings->enable_discounts) && $this->settings->enable_discounts && false) {
                    $discounts = Product::find()
                        ->published()
                        ->isNotEmpty('discount')->count();
                    if ($discounts) {
                        $keyboardsFirst[] = [new InlineKeyboardButton([
                            'text' => "🔥 Акции ({$discounts})",
                            'callback_data' => 'query=getList&model=discounts'
                        ])];
                    }
                }
                if (isset($this->settings->enable_new) && $this->settings->enable_new) {
                    $new = Product::find();
                    if (isset($this->settings->label_expire_new)) {
                        $new->int2between(time(), time() - (86400 * $this->settings->label_expire_new));
                    } else {
                        $new->int2between(-1, -1);
                    }
                    $newCount = $new->count();
                    if ($newCount) {
                        $keyboardsFirst[] = [new InlineKeyboardButton([
                            'text' => "❇️ Новинки ({$newCount})",
                            'callback_data' => 'query=getList&model=new'
                        ])];
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
                        'text' => "💎️ Бренды ({$brands})",
                        'callback_data' => 'query=getBrandsList'
                    ])]);
                }
            }

            if (isset($this->settings->enable_discounts) && $this->settings->enable_discounts && false) {
                $discounts = Product::find()
                    ->published()
                    ->isNotEmpty('discount')->count();
                if ($discounts) {
                    $keyboardsFirst[] = [new InlineKeyboardButton([
                        'text' => "🔥 Акции ({$discounts})",
                        'callback_data' => 'query=getList&model=discounts'
                    ])];
                }
            }

            if (isset($this->settings->enable_new) && $this->settings->enable_new) {
                $new = Product::find();
                if (isset($this->settings->label_expire_new)) {
                    $new->int2between(time(), time() - (86400 * $this->settings->label_expire_new));
                } else {
                    $new->int2between(-1, -1);
                }
                $newCount = $new->count();
                if ($newCount) {
                    $keyboardsFirst[] = [new InlineKeyboardButton([
                        'text' => "❇️ Новинки ({$newCount})",
                        'callback_data' => 'query=getList&model=new'
                    ])];
                }
            }
            $keyboards = ArrayHelper::merge($keyboardsFirst, $keyboards);

            $data = [
                'chat_id' => $chat_id,
                'text' => 'Выберите раздел:',
                'reply_markup' => new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]),
            ];


            $dataCatalog['text'] = '⬇ Каталог продукции';
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
