<?php

namespace shopium\mod\telegram\components\Commands\AdminCommands;

use core\modules\shop\models\Product;
use core\modules\user\models\Payments;
use core\modules\user\models\User;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use panix\engine\CMS;
use shopium\mod\cart\models\Payment;
use shopium\mod\telegram\components\AdminCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;

/**
 * User "/plan" command
 */
class PlanCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'plan';

    /**
     * @var string
     */
    protected $description = 'Информация о тарифе';

    /**
     * @var string
     */
    protected $usage = '/plan';

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * @var bool
     */
    protected $show_in_help = false;
    protected $enabled = false;

    // public $user_id;
    public $system;
    public $month;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $userData = Yii::$app->user;
        $update = $this->getUpdate();
        $configure = Yii::$app->settings->get('app');
        $planName = Yii::$app->params['plan'][$userData->planId]['name'];

        /** @var User $model */
        /*$model = User::findModel(Yii::$app->user->id);
        $percent = 0.03;
        $money = 300.00;
        $model->money += $money + ($money / 100 * $percent);
        $model->save(false);*/

        /*$payment = new Payments();
        $payment->system = 'liqpay';
        $payment->type = 'balance';*/


        // if (($this->user_id = $this->getConfig('user_id')) === '') {
        //     $this->user_id = false;
        // }
        if (($this->system = $this->getConfig('system')) === '') {
            $this->system = false;
        }
        if (($this->month = $this->getConfig('month')) === '') {
            $this->month = false;
        }


        $payment = new Payments();
        $payment->system = 'liqpay';
        $payment->name = 'Пополнение баланса';
        $payment->type = 'balance';
        $payment->money = 300.00;
        $payment->save(false);
        $paymentAdd = $payment;


        $payment2 = new Payments();
        $payment2->system = 'liqpay';
        $payment2->name = "Помолнение тарифтоного плана {$planName} на {$this->month} месяц";
        $payment2->type = 'balance';
        $payment2->money -= $paymentAdd->money;
        $payment2->save(false);


        $isCallback = false;
        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $user = $callbackQuery->getFrom();

            $isCallback = true;
        } else {
            $message = $this->getMessage();
            $user = $message->getFrom();

        }
        $chat = $message->getChat();
        $chat_id = $chat->getId();
        $user_id = $user->getId();
        /*if ($isCallback) {
            $planName = Yii::$app->params['plan'][$userData->planId]['name'];
            $price2 = Yii::$app->params['plan'][$userData->planId]['prices'][$this->month] * $this->month;
            if ($this->system == 'liqpay') {

                $price = Yii::$app->params['plan'][$userData->planId]['prices'][1] * $this->month;
                if(isset($configure->liqpay_percent) && $configure->liqpay_percent){
                    $percent = 0.03;
                    $label = "{$this->month} мес. + (Комиссия {$percent}%)";
                    $amount = $price + ($price2 / 100 * $percent);
                }else{
                    $label = "{$this->month} мес.";
                    $amount = $price;
                }

                $prices[] = new LabeledPrice([
                    'label' => $label,
                    'amount' => number_format($amount,2,'','')
                ]);


                if ($this->month >= 12) {
                    $prices[] = new LabeledPrice([
                        'label' => "Скидка при оплате за год",
                        'amount' => number_format($price2 - $price,2,'','')
                    ]);
                }
                $inline_keyboard = new InlineKeyboard([
                    [
                        'text' => "Оплатить " . Yii::$app->currency->number_format($price2) . " грн / {$this->month} мес.",
                        'pay' => true
                    ],
                ]);


                $data['chat_id'] = $chat_id;

                $data['title'] = 'Оплата тарифного плана';
                $data['description'] = "Оплата тарифного плана \"{$planName}\" на {$this->month} мес.";
                $data['payload'] = "plan={$userData->planId}&month={$this->month}";
                $data['provider_token'] = Yii::$app->params['payment']['liqpay']['provider'];
                $data['start_parameter'] = $userData->planId . '-' . $this->month . '-' . CMS::gen(20);
                $data['currency'] = 'UAH';
                $data['prices'] = $prices;
                $data['reply_markup'] = $inline_keyboard;
                $data['reply_to_message_id'] = $message->getMessageId();
                $pay = Request::sendInvoice($data);
                if (!$pay->getOk()) {

                    $this->notify($pay->getDescription(),'error');
                }else{
                    $this->notify($pay->getDescription());
                }
                return $pay;
            }
        }*/


        $admins = $this->telegram->getAdminList();
        $productCount = Product::find()->count();

        /*foreach (Yii::$app->params['plan'][$userData->planId]['prices'] as $months => $price) {
            $pr = Yii::$app->currency->number_format($price * $months);
            $keyboards[] = [
                new InlineKeyboardButton([
                    'text' => "🇺🇦 LiqPay — {$pr} грн. / {$months} мес.",
                    'callback_data' => "query=planPay&system=liqpay&month={$months}"
                ])
            ];
        }*/


        $text = '';
        $text .= 'Текущий тариф: *' . Yii::$app->params['plan'][$userData->planId]['name'] . '*' . PHP_EOL;
        $text .= 'Доступно товаров: *' . $productCount . '* из *' . Yii::$app->params['plan'][$userData->planId]['product_limit'] . '* '.Yii::t('shop/Product','UNIT_THING') . PHP_EOL;

        $text .= 'Работает до: *' . CMS::date($userData->expire) . '*' . PHP_EOL . PHP_EOL;

        //remove default admin from list
        foreach ($this->telegram->defaultAdmins as $k => $a) {
            unset($admins[$k]);
        }
        if ($admins) {
            $text .= 'Администраторы:' . PHP_EOL;
            foreach ($admins as $admin) {
                $text .= '/whois' . $admin . '' . PHP_EOL;
            }
        }

        $text .= '⬇️ Продлить периуд:' . PHP_EOL;

        $data['chat_id'] = $message->getFrom()->getId();
        $data['text'] = $text;
        $data['parse_mode'] = 'Markdown';
        /*$data['reply_markup'] = new InlineKeyboard([
            'inline_keyboard' => $keyboards
        ]);*/
        return Request::sendMessage($data);
    }
}
