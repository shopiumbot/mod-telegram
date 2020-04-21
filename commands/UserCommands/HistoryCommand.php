<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace shopium\mod\telegram\commands\UserCommands;


use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\InlineKeyboardPager;
use shopium\mod\telegram\components\KeyboardPagination;
use shopium\mod\telegram\components\UserCommand;
use shopium\mod\cart\models\Order;
use Yii;

/**
 * User "/history" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class HistoryCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'history';

    /**
     * @var string
     */
    protected $description = 'Моя история заказов';

    /**
     * @var string
     */
    protected $usage = '/history';

    /**
     * @var string
     */
    protected $version = '1.0.0';
    private $page = 0;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $chat = $message->getChat();
            $user = $callbackQuery->getFrom();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
        } else {
            $callbackQuery = null;
            $message = $this->getMessage();
            $chat = $message->getChat();
            $user = $message->getFrom();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
        }
        $data['chat_id'] = $chat_id;

        $text = trim($message->getText(true));
        if ($this->getConfig('page')) {
            $this->page = $this->getConfig('page');
        }

        $query = Order::find()->where(['user_id' => $user_id, 'checkout' => 1]);
        $pages = new KeyboardPagination([
            'totalCount' => $query->count(),
            'defaultPageSize' => 1,
            //'pageSize'=>3
        ]);
        $pages->setPage($this->page);
        $orders = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();


        $pager = new InlineKeyboardPager([
            'pagination' => $pages,
            'lastPageLabel' => false,
            'firstPageLabel' => false,
            'maxButtonCount' => 1,
            'command' => 'getHistory'
        ]);


        if ($orders) {



            $text = '*История заказа*' . PHP_EOL . PHP_EOL;
            foreach ($orders as $order) {
                if ($pager->buttons)
                    $keyboards[] = $pager->buttons;

                if($order->paid) {
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/command', '✅ ОПЛАЧЕНО!'),
                            'callback_data' => time()
                        ])];
                }else{
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/command', 'BUTTON_PAY', $this->number_format($order->total_price)),
                            'callback_data' => 'payment/' . $order->id
                        ])];
                }
                foreach ($order->products as $product) {
                    $text .= '*' . $product->name . '* `' . $product->quantity . 'шт. / ' . $product->price . ' грн. `' . PHP_EOL;
                }
                $text .= PHP_EOL . PHP_EOL . 'Доставка: *' . $order->deliveryMethod->name . '*' . PHP_EOL;
                $text .= 'Оплата: *' . $order->paymentMethod->name . '*' . PHP_EOL;
                $text .= 'Общая стоимость заказа: *' . $order->total_price . ' грн.*' . PHP_EOL;

            }
            $data['text'] = $text;
            $data['parse_mode'] = 'Markdown';

            if ($keyboards) {
                $data['reply_markup'] = new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]);
            }

            if ($callbackQuery) {

                $data['message_id'] = $message->getMessageId();
                $response = Request::editMessageText($data);

                $dataReplyMarkup['reply_markup'] = new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]);

                return Request::editMessageReplyMarkup(array_merge($data, $dataReplyMarkup));
            }
            $response = $data;


        } else {
            $data['text'] = Yii::$app->settings->get('telegram', 'empty_history_text');
            $data['reply_markup'] = $this->startKeyboards();
            $response = $data;

        }
        return Request::sendMessage($response);
    }
}
