<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\cart\components\payment\BasePaymentSystem;
use shopium\mod\cart\models\Order;
use shopium\mod\cart\models\Payment;
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

        /*$callback_query = $update->getCallbackQuery();
        $message = $callback_query->getMessage();
        $chat_id = $message->getChat()->getId();


        // $chat = $message->getChat();
        $user = $message->getFrom();
        $user_id = $user->getId();


        $callback_query_id = $callback_query->getId();
        $callback_data = $callback_query->getData();*/


        $data = [];
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


        $config = Yii::$app->settings->get('app');

        $data['chat_id'] = $chat_id;


        if ($this->order_id) {
            $order = Order::findOne($this->order_id);
            if ($order) {

                $prices = [];
                $data['title'] = 'Номер заказа №' . CMS::idToNumber($order->id);
                if ($order->paymentMethod) {
                    $settings = false;
                    $model = Payment::findOne($order->payment_id);
                    $system = $model->getPaymentSystemClass();
                    if ($system instanceof BasePaymentSystem) {
                        $settings = $system->getSettings($order->payment_id);
                    }
                    $total_price = $order->total_price;

                    if ($order->paymentMethod->system == 'liqpay') {
                        $data['currency'] = 'UAH';
                        $data['description'] = 'Оплата заказа';
                        $data['payload'] = 'order-' . $order->id;
                        $data['start_parameter'] = CMS::gen(10);
                        $data['provider_token'] = $settings->key;

                        //$params['amount'] = $price + ($price / 100 * 2.75);

                        if ($settings->commission_check) {
                            $total_price = $order->total_price + ($order->total_price / 100 * 2.75);

                            $prices[] = new LabeledPrice([
                                'label' => 'Комиссия (2.75%)',
                                'amount' => number_format($order->total_price - ($order->total_price / 100 * 2.75), 2, '', '')
                            ]);
                        }

                    }


                    foreach ($order->products as $product) {

                        $prices[] = new LabeledPrice([
                            'label' => $product->name . ' (' . $product->quantity . ' шт.)',
                            'amount' => number_format($product->price * $product->quantity, 2, '', '')
                        ]);
                    }
                    $inline_keyboard = new InlineKeyboard([
                        [
                            'text' => Yii::t('telegram/command', 'BUTTON_PAY', [
                                'value' => Yii::$app->currency->number_format($total_price),
                                'currency' => $data['currency']
                            ]),
                            'pay' => true
                        ],
                    ]);


                    $data['description'] = 'Оплата заказа';
                    $data['payload'] = 'order-' . $order->id;
                    $data['start_parameter'] = CMS::gen(10);
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
        return $this->notify(Yii::t('telegram/default', 'PAYMENT_SYSTEM_NO_CONFIG'));

    }


}
