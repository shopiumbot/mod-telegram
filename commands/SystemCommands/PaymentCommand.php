<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace shopium\mod\telegram\commands\SystemCommands;

use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use Longman\TelegramBot\Entities\ReplyKeyboardHide;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\telegram\components\SystemCommand;

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
    public $private_only=true;
    public $order_id;
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $update = $this->getUpdate();

        $callback_query = $update->getCallbackQuery();
        $message = $callback_query->getMessage();
        $chat_id = $message->getChat()->getId();


        // $chat = $message->getChat();
        $user = $message->getFrom();
        $user_id = $user->getId();


        $callback_query_id = $callback_query->getId();
        $callback_data = $callback_query->getData();


       // echo $this->order_id.'zzzzzzz';
       // $order = Order::find()->where(['id'=>$this->order_id])->one();

       // echo $order->total_price;
if(false){
        $prices[] = new LabeledPrice(['label'=>'UAH','amount'=>500]);
        $data['chat_id']=$chat_id;
        $data['title']='title';
        $data['description']='description';
        $data['payload']='order-123123123';
        $data['provider_token']='632593626:TEST:i56982357197';
        $data['start_parameter']=CMS::gen(10);
        $data['currency']='UAH';
        $data['prices']=$prices;
        $pay = Request::sendInvoice($data);
        print_r($pay);
}

        return $this->notify('Система оплаты не настроена','info');
    }


}
