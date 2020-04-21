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
use shopium\mod\cart\models\OrderProduct;

/**
 *
 * Display an inline keyboard with a few buttons.
 */
class CatalogproductquantityCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'catalogproductquantity';

    /**
     * @var string
     */
    protected $description = 'Change product quantity in catalog';

    /**
     * @var string
     */
    protected $version = '1.0.0';
    public $product_id;
    public $quantity;
    public $order_id;
    private $chat_id;

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
        if (($this->quantity = trim($this->getConfig('quantity'))) === '') {
            $this->quantity = NULL;
        }
        if (($this->order_id = trim($this->getConfig('order_id'))) === '') {
            $this->order_id = NULL;
        }
        $update = $this->getUpdate();
        $callback_query = $update->getCallbackQuery();
        if ($callback_query) {
            $message = $callback_query->getMessage();
            $callback_query_id = $callback_query->getId();
        } else {
            $message = $this->getMessage();
        }


        $product = OrderProduct::find()->where(['order_id' => $this->order_id, 'product_id' => $this->product_id])->one();
        $chat_id = $message->getChat()->getId();
        //  $order = OrderProduct::find()->where(['order_id'=>$this->order_id]);
        if ($product) {
            $keyboards[] = [
                new InlineKeyboardButton([
                    'text' => '—',
                    //'callback_data' => "spinner/{$this->order_id}/{$this->product_id}/down/catalog"
                    'callback_data' => "query=productSpinner&order_id={$this->order_id}&product_id={$this->product_id}&type=down"
                ]),
                new InlineKeyboardButton([
                    'text' => '' . $this->quantity . ' шт.',
                    'callback_data' => time()
                ]),
                new InlineKeyboardButton([
                    'text' => '+',
                    // 'callback_data' => "spinner/{$this->order_id}/{$this->product_id}/up/catalog"
                    'callback_data' => "query=productSpinner&order_id={$this->order_id}&product_id={$this->product_id}&type=up"
                ]),
                new InlineKeyboardButton([
                    'text' => "❌",
                    //'callback_data' => "cartDeleteInCatalog/{$this->order_id}/{$this->product_id}"
                    'callback_data' => "query=deleteInCart&id={$product->id}"
                ]),
            ];
        }
        $keyboards[] = $this->productAdminKeywords($chat_id, $this->product_id);

        $dataEdit['chat_id'] = $chat_id;
        $dataEdit['message_id'] = $message->getMessageId();
        $dataEdit['reply_markup'] = new InlineKeyboard([
            'inline_keyboard' => $keyboards
        ]);


        return Request::editMessageReplyMarkup($dataEdit);
    }

}
