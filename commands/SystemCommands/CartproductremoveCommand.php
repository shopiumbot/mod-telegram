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


use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\SystemCommand;
use shopium\mod\cart\models\OrderProduct;
use Yii;
/**
 *
 * Display an inline keyboard with a few buttons.
 */
class CartproductremoveCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'cartproductremove';

    /**
     * @var string
     */
    protected $description = 'Remove product in cart';

    /**
     * @var string
     */
    protected $version = '1.0.0';
    public $id;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {

        if (($this->id = trim($this->getConfig('id'))) === '') {
            $this->id = NULL;
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


        $orderProduct = OrderProduct::findOne($this->id);


        if($orderProduct){
            $originalProduct = $orderProduct->originalProduct;
            $orderProduct->delete();

            return $this->telegram->executeCommand('cart');


           /* $keyboards[] = [
                new InlineKeyboardButton([
                    'text' => Yii::t('telegram/command','BUTTON_BUY',$originalProduct->price),
                     'callback_data' => "query=addCart&product_id={$originalProduct->product_id}"
                ])
            ];

            $dataEdit['chat_id'] = $chat_id;
            $dataEdit['message_id'] = $message->getMessageId();
            $dataEdit['reply_markup'] = new InlineKeyboard([
                'inline_keyboard' => $keyboards
            ]);


            return Request::editMessageReplyMarkup($dataEdit);*/
        }

        return Request::emptyResponse();
    }
}
