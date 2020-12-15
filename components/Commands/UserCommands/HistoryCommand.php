<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;


use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\cart\models\NovaPoshtaWarehouses;
use shopium\mod\telegram\components\InlineKeyboardPager;
use shopium\mod\telegram\components\KeyboardPagination;
use shopium\mod\telegram\components\UserCommand;
use shopium\mod\cart\models\Order;
use Yii;
use yii\helpers\Url;

/**
 * User "/history" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class HistoryCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'history';

    /**
     * @var string
     */
    protected $usage = '/history';

    /**
     * @var string
     */
    protected $version = '1.0.0';
    private $page = 0;

    public function getDescription()
    {
        return Yii::t('telegram/default', 'COMMAND_HISTORY');
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
            $user = $callbackQuery->getFrom();

        } else {
            $callbackQuery = null;
            $message = $this->getMessage();
            $user = $message->getFrom();

        }
        $chat = $message->getChat();
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $this->setLanguage($user_id);
        $data['chat_id'] = $chat_id;

        $text = trim($message->getText(true));
        if ($this->getConfig('page')) {
            $this->page = $this->getConfig('page');
        }

        $query = Order::find()
            ->where(['user_id' => $user_id])
            ->orderBy(['id' => SORT_DESC]);

        $pages = new KeyboardPagination([
            'totalCount' => $query->count(),
            'defaultPageSize' => 1,
            //'pageSize'=>3
        ]);
        $pages->setPage($this->page);
        $orders = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();


        $pager = new InlineKeyboardPager([
            'pagination' => $pages,
            'lastPageLabel' => false,
            'firstPageLabel' => false,
            'maxButtonCount' => 1,
            'command' => 'getHistory'
        ]);

        $keyboards = [];
        if ($orders) {


            $text = '*'.Yii::t('cart/default','ORDER_HISTORY').'*' . PHP_EOL . PHP_EOL;

            foreach ($orders as $order) {
                $text .= ''.Yii::t('cart/Order','ORDER_ID').' *№' . CMS::idToNumber($order->id) . '*' . PHP_EOL . PHP_EOL;
                if ($pager->buttons)
                    $keyboards[] = $pager->buttons;

                if ($order->paid) {
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => '✅ '.Yii::t('telegram/default', 'PAID'),
                            'callback_data' => time()
                        ])];
                } else {
                    $system = ($order->paymentMethod) ? $order->paymentMethod->system : 0;
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/default', 'BUTTON_PAY', [
                                'price' => $this->number_format($order->total_price),
                                'currency' => Yii::$app->currency->active['symbol']
                            ]),
                            'callback_data' => "query=orderPay&id={$order->id}&system={$system}"
                        ])];
                }
                foreach ($order->products as $product) {
                    $command = '';
                    if ($product->originalProduct) {
                        $command .= '/product' . $product->product_id;
                    }
                    // $text .= '[' . $product->name . ']('.Url::to($product->originalProduct->getImage()->getUrlToOrigin(),true).') *(' . $product->quantity . ' шт.)*: ' . Yii::$app->currency->number_format($product->price) . ' грн. ' . PHP_EOL;
                    $text .= '*' . $product->name . '* ' . $command . ' *(' . $product->quantity . ' '.Yii::t('shop/Product','UNIT_THING').'):* ' . $this->number_format($product->price) . ' '.Yii::$app->currency->active['symbol'] . PHP_EOL;
                }

                $text .= PHP_EOL . ''.Yii::t('cart/Order','CREATED_AT').': *' . CMS::date($order->created_at) . '*' . PHP_EOL;
                if ($order->status)
                    $text .= ''.Yii::t('cart/Order','STATUS_ID').': *' . $order->status->name . '*' . PHP_EOL;
                if ($order->invoice && !empty($order->invoice)) {
                    $text .= 'TTH: *' . $order->invoice . '*' . PHP_EOL;
                }

                if ($order->deliveryMethod) {
                    $text .= PHP_EOL . PHP_EOL . '🚚 '.Yii::t('cart/default','DELIVERY').': *' . $order->deliveryMethod->name . '*' . PHP_EOL;
                }
                if ($order->area_id && $order->area) {
                    $text .= 'обл. *' . $order->area . '*, ';
                }
                if ($order->city_id && $order->city) {
                    $text .= 'г. *' . $order->city . '*' . PHP_EOL;
                }

                if ($order->warehouse_id && $order->warehouse) {
                    $warehouse = NovaPoshtaWarehouses::findOne(['Ref' => trim($order->warehouse_id)]);
                    if ($warehouse) {
                        $text .= '*' . $warehouse->DescriptionRu . '*' . PHP_EOL;
                    } else {
                        $text .= 'Отделение: *' . $order->warehouse . ' ' . $order->warehouse_id . '*' . PHP_EOL;
                    }
                }
                if ($order->paymentMethod) {
                    $text .= PHP_EOL . '💰 '.Yii::t('cart/default','PAYMENT').': *' . $order->paymentMethod->name . '*' . PHP_EOL;
                }
                $text .= Yii::t('cart/default','TOTAL_COST').': *' . $this->number_format($order->total_price) . ' ' . Yii::$app->currency->active['symbol'] . '*' . PHP_EOL;

            }
            $data['text'] = $text;
            $data['parse_mode'] = 'Markdown';

            if ($keyboards) {
                $data['reply_markup'] = new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]);
            }

            if ($callbackQuery) {

                $data['message_id'] = $message->getMessageId();
                $response = Request::editMessageText($data);
                if (!$response->isOk()) {
                    return $this->notify($data['message_id'] . ' editMessageText: ' . $response->getDescription(), 'error');
                }
                return $response;

            }
            $response = $data;


        } else {
            $data['text'] = Yii::t('telegram/default','HISTORY_EMPTY');
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
