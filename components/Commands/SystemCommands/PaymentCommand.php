<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\cart\models\Order;
use shopium\mod\telegram\components\SystemCommand;
use Yii;

/**
 *
 * This command cancels the currently active conversation and
 * returns a message to let the user know which conversation it was.
 * If no conversation is active, the returned message says so.
 */
class PaymentCommand extends SystemCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'payment';
    protected $description = 'payment order';

    protected $version = '1.0.0';
    protected $need_mysql = true;
    public $enabled = true;
    public $private_only = true;
    public $order_id;
    public $system;

    /**
     * {@inheritdoc}
     */
    public function execute()
    {

        if (($this->order_id = $this->getConfig('order_id')) === '') {
            $this->order_id = false;
        }

        if (($this->system = $this->getConfig('system')) === '') {
            $this->system = false;
        }

        $update = $this->getUpdate();

        $callback_query = $update->getCallbackQuery();
        $message = $callback_query->getMessage();
        $chat_id = $message->getChat()->getId();


        // $chat = $message->getChat();
        $user = $message->getFrom();
        $user_id = $user->getId();


        $callback_query_id = $callback_query->getId();
        $callback_data = $callback_query->getData();

        $config = Yii::$app->settings->get('app');
        if ($this->order_id) {
            $order = Order::findOne($this->order_id);
            if ($order) {
                if (isset($config->liqpay_provider) && !empty($config->liqpay_provider)) {
                    $prices = [];
                    foreach ($order->products as $product) {
                        $prices[] = new LabeledPrice(['label' => $product->name . ' (' . $product->quantity . ' шт.)', 'amount' => $product->price * $product->quantity]);
                        //$prices[] = new LabeledPrice(['label' => $product->name . ' (' . $product->quantity . ' шт.)', 'amount' => 100]);
                    }
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => 'Оплатить ' . Yii::$app->currency->number_format($order->total_price) . ' грн', 'pay' => true],
                    ]);


                    $data['chat_id'] = $chat_id;
                    $data['title'] = 'Номер заказа №' . CMS::idToNumber($order->id);
                    $data['description'] = 'Оплата заказа';
                    $data['payload'] = 'order-' . $order->id;
                    $data['provider_token'] = $config->liqpay_provider;
                    //$data['provider_token'] = '635983722:LIVE:i50530989846';
                    $data['start_parameter'] = CMS::gen(10);
                    $data['currency'] = 'UAH';
                    $data['prices'] = $prices;
                    $data['reply_markup'] = $inline_keyboard;
                    $data['reply_to_message_id'] = $message->getMessageId();
                    $pay = Request::sendInvoice($data);
                    if (!$pay->getOk()) {
                        $this->notify($pay->getDescription());
                    }
                    return $pay;
                }
            }
        }
        return $this->notify('Система оплаты не настроена');

    }


}
