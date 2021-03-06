<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;


use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\SystemCommand;
use shopium\mod\cart\models\Order;
use shopium\mod\cart\models\OrderProduct;
use Yii;
/**
 *
 * Display an inline keyboard with a few buttons.
 */
class CatalogcartproductremoveCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'catalogcartproductremove';

    /**
     * @var string
     */
    protected $description = 'Remove product in cart catalog';

    /**
     * @var string
     */
    protected $version = '1.0.0';
    public $product_id;
    public $order_id;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {

        if (($this->product_id = trim($this->getConfig('product_id'))) === '') {
            $this->product_id = NULL;
        }
        if (($this->order_id = trim($this->getConfig('order_id'))) === '') {
            $this->order_id = NULL;
        }




        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {
            $message = $update->getCallbackQuery()->getMessage();
            $user_id = $update->getCallbackQuery()->getFrom()->getId();
        } else {
            $message = $this->getMessage();
            $user_id = $message->getFrom()->getId();
        }


        $chat_id = $message->getChat()->getId();

        $order = Order::findOne($this->order_id);
        $orderProduct = OrderProduct::findOne(['product_id' => $this->product_id, 'order_id' => $order->id]);
        if($orderProduct){
          //  $originalProduct = $orderProduct->originalProduct;
            $keyboards[] = [
                new InlineKeyboardButton([
                    'text' => Yii::t('telegram/default','BUTTON_BUY',[
                        'price'=>$this->number_format($orderProduct->price),
                        'currency'=>Yii::$app->currency->active['symbol']
                    ]),
                    'callback_data' => "query=addCart&product_id={$orderProduct->product_id}"
                ])
            ];
            $orderProduct->delete();



           /* if ($this->telegram->isAdmin($chat_id)) {
                $keyboards[] = [
                    new InlineKeyboardButton(['text' => '✏', 'callback_data' => "productUpdate/{$originalProduct->id}"]),
                    new InlineKeyboardButton(['text' => '❌', 'callback_data' => "productDelete/{$originalProduct->id}"]),
                    new InlineKeyboardButton(['text' => '👁', 'callback_data' => "productHide/{$originalProduct->id}"])
                ];
            }*/


            $dataEdit['chat_id'] = $chat_id;
            $dataEdit['message_id'] = $message->getMessageId();
            $dataEdit['reply_markup'] = new InlineKeyboard([
                'inline_keyboard' => $keyboards
            ]);


            return Request::editMessageReplyMarkup($dataEdit);
        }

        return Request::emptyResponse();
    }
}
