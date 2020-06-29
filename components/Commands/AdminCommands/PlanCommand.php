<?php

namespace shopium\mod\telegram\components\Commands\AdminCommands;

use core\modules\shop\models\Product;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use panix\engine\CMS;
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

    // public $user_id;
    public $system;
    public $month;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute()
    {
        $userData = Yii::$app->user;
        $update = $this->getUpdate();
        // if (($this->user_id = $this->getConfig('user_id')) === '') {
        //     $this->user_id = false;
        // }
        if (($this->system = $this->getConfig('system')) === '') {
            $this->system = false;
        }
        if (($this->month = $this->getConfig('month')) === '') {
            $this->month = false;
        }
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
        if ($isCallback) {
            $planName = Yii::$app->params['plan'][$userData->planId]['name'];
            $price = Yii::$app->params['plan'][$userData->planId]['prices'][$this->month] * $this->month;
            if ($this->system == 'liqpay') {

                $prices[] = new LabeledPrice([
                    'label' => "{$this->month} мес.",
                    'amount' => $price . '00'
                ]);
                /*$prices[] = new LabeledPrice([
                    'label' => "Коммисия",
                    'amount' => 003
                ]);*/
                $inline_keyboard = new InlineKeyboard([
                    [
                        'text' => "Оплатить " . Yii::$app->currency->number_format($price) . " грн / {$this->month} мес.",
                        'pay' => true
                    ],
                ]);


                $data['chat_id'] = $chat_id;
                $data['title'] = 'Оплата тарифного плана';
                $data['description'] = "Оплата тарифного плана \"{$planName}\" на {$this->month} мес.";
                $data['payload'] = "plan={$userData->planId}&id=123";
                $data['provider_token'] = Yii::$app->params['payment']['liqpay']['provider'];
                $data['start_parameter'] = '' . CMS::gen(10);
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


        $admins = $this->telegram->getAdminList();
        $productCount = Product::find()->count();

        foreach (Yii::$app->params['plan'][$userData->planId]['prices'] as $months => $price) {
            $pr = Yii::$app->currency->number_format($price * $months);
            $keyboards[] = [
                new InlineKeyboardButton([
                    'text' => "🇺🇦 LiqPay — {$pr} грн. / {$months} мес.",
                    'callback_data' => "query=planPay&system=liqpay&month={$months}"
                ])
            ];
        }


        $text = '';
        $text .= 'Текущий тариф: *' . Yii::$app->params['plan'][$userData->planId]['name'] . '*' . PHP_EOL;
        $text .= 'Доступно товаров: *' . $productCount . '* из *' . Yii::$app->params['plan'][$userData->planId]['product_limit'] . '* шт.' . PHP_EOL;

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
        $data['reply_markup'] = new InlineKeyboard([
            'inline_keyboard' => $keyboards
        ]);
        return Request::sendMessage($data);
    }
}
