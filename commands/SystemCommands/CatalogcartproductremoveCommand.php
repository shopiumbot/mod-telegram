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


use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
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
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
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
                    'text' => Yii::t('telegram/command','BUTTON_BUY',$orderProduct->price),
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
