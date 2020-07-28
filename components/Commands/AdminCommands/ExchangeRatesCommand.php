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
use yii\base\Exception;
use yii\httpclient\Client;

/**
 * User "/exchangerates" command
 */
class ExchangeRatesCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'exchangerates';

    /**
     * @var string
     */
    protected $description = 'Курс валют';

    /**
     * @var string
     */
    protected $usage = '/exchangerates';

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * @var bool
     */
    protected $show_in_help = false;
    protected $enabled = true;


    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute()
    {

        $update = $this->getUpdate();

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


        $data['chat_id'] = $chat_id;

        $data['parse_mode'] = 'Markdown';
        /*$data['reply_markup'] = new InlineKeyboard([
            'inline_keyboard' => $keyboards
        ]);*/


        $client = new Client();
        /* $response = $client->createRequest()
             ->setMethod('GET')
             ->setUrl('https://api.privatbank.ua/p24api/exchange_rates')
             ->setData(['json' => true, 'date' => date('d.m.Y')])
             ->send();
         if ($response->isOk) {

             foreach ($response->data['exchangeRate'] as $rate){
                 $data['text'] = json_encode($response->data);
             }
             return Request::sendMessage($data);
         }*/
        $text = 'Курс на  *' . CMS::date(time(), false) . '*' . PHP_EOL . PHP_EOL;

        try {
            //приват банк
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl('https://api.privatbank.ua/p24api/pubinfo')
                ->setData(['json' => true, 'exchange' => true, 'coursid' => 5])
                ->send();
            if ($response->isOk) {
                $text .= '🇺🇦 🏦 *ПриватБанк*' . PHP_EOL;
                foreach ($response->data as $rate_pb) {
                    $buy = $this->number_format($rate_pb['buy']);
                    $sale = $this->number_format($rate_pb['sale']);
                    //$text .= "`{$rate_pb['ccy']}`" . PHP_EOL;
                    /*$text .= "покупка: *{$buy}* {$rate_pb['base_ccy']}" . PHP_EOL;
                    $text .= "продажа: *{$sale}* {$rate_pb['base_ccy']}" . PHP_EOL . PHP_EOL;*/

                    $text .= "*{$rate_pb['ccy']}*: `{$buy} / {$sale} {$rate_pb['base_ccy']}`" . PHP_EOL;

                }
                // $data['text'] = $text;
                $text .= "" . PHP_EOL;
            }
        } catch (Exception $e) {
            $text .= 'ПриватБанк Connection error';
        }
        try {

            //Нац банк Украины USD
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl('https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange')
                ->setData(['json' => true, 'date' => date('Ymd')])
                ->send();
            if ($response->isOk) {
                $text .= '🇺🇦 🏦 *НБУ*' . PHP_EOL;
                foreach ($response->data as $rate_nb) {

                    if (in_array($rate_nb['cc'], ['USD', 'EUR', 'RUB'])) {
                        $sale = $this->number_format($rate_nb['rate']);
                        /* $text .= "`{$rate_nb['cc']} ({$rate_nb['txt']})`" . PHP_EOL;
                         $text .= "покупка: *{$sale}* UAH" . PHP_EOL . PHP_EOL;*/
                        //  $text .= "`{$rate_nb['cc']} ({$rate_nb['txt']})`" . PHP_EOL;
                        $text .= "*{$rate_nb['cc']}*: `{$sale} UAH`" . PHP_EOL;
                    }

                }
            }
        } catch (Exception $e) {
            $text .= '🇺🇦 🏦 *НБУ* Connection error';
        }
        try {
            $text .= "" . PHP_EOL;
            //Россия
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl('https://www.cbr-xml-daily.ru/daily_json.js')
                //->setData(['json' => true, 'date' => date('Ymd')])
                ->send();
            if ($response->isOk) {
                $text .= '🇷🇺 🏦 *ЦБР*' . PHP_EOL;
                foreach ($response->data['Valute'] as $cc => $rate_rus) {

                    if (in_array($cc, ['USD', 'EUR', 'UAH'])) {
                        $sale = $this->number_format($rate_rus['Value']);
                        /* $text .= "`{$rate_nb['cc']} ({$rate_nb['txt']})`" . PHP_EOL;
                         $text .= "покупка: *{$sale}* UAH" . PHP_EOL . PHP_EOL;*/
                        //  $text .= "`{$rate_nb['cc']} ({$rate_nb['txt']})`" . PHP_EOL;
                        $text .= "*{$cc}*: `{$sale} RUB`" . PHP_EOL;
                    }

                }
            }
        } catch (Exception $e) {
            $text .= 'ЦБР Connection error';
        }


        $data['text'] = $text;
        return Request::sendMessage($data);


    }
}
