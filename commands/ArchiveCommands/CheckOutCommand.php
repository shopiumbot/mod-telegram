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


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use panix\mod\cart\models\Delivery;
use panix\mod\cart\models\Payment;
use shopium\mod\telegram\components\SystemCommand;
use shopium\mod\cart\models\Order;
use Yii;

/**
 * User "/checkout" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class CheckOutCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'checkout';

    /**
     * @var string
     */
    protected $description = 'checkout';

    /**
     * @var string
     */
    protected $usage = '/checkout';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Conversation Object
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;
    public $id;

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
            //  $chat = $callbackQuery->getMessage()->getChat();
            //  $user = $message->getFrom();
            $chat = $message->getChat();
            $user = $callbackQuery->getFrom();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
            parse_str($callbackQuery->getData(), $params);
            $order = Order::find()->where(['id' => $params['id'], 'checkout' => 0])->one();

        } else {
            $message = $this->getMessage();
            $chat = $message->getChat();
            $user = $message->getFrom();

            $chat_id = $chat->getId();
            $user_id = $user->getId();
            $order = Order::find()->where(['client_id' => $user_id, 'checkout' => 0])->one();
        }


        $data['chat_id'] = $chat_id;
        $text = trim($message->getText(true));

        /*$order = Order::find()->where(['client_id' => $user_id, 'checkout' => 0])->one();
        if (!$order || !$order->getProducts()->count()) {
            $data['text'] = Yii::$app->settings->get('telegram', 'empty_cart_text');
            $data['reply_markup'] = $this->startKeyboards();
             return Request::sendMessage($data);
        }*/

        //Preparing Response

        if ($text === '❌ Отмена') {
            $this->telegram->executeCommand('cancel');
            return Request::emptyResponse();
        }

        if ($order) {

            if ($chat->isGroupChat() || $chat->isSuperGroup()) {
                //reply to message id is applied by default
                //Force reply is applied by default so it can work with privacy on
                $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
            }

            //Conversation start
            $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

            $notes = &$this->conversation->notes;
            !is_array($notes) && $notes = [];

            //cache data from the tracking session if any
            $state = 0;
            if (isset($notes['state'])) {
                $state = $notes['state'];
            }


            $result = Request::emptyResponse();


            //State machine
            //Entrypoint of the machine state if given by the track
            //Every time a step is achieved the track is updated
            switch ($state) {
                case 0:
                    if ($text === '' || !in_array($text, ['➡ Продолжить', '❌ Отмена'], true)) {
                        $notes['state'] = 0;
                        $this->conversation->update();

                        $data['reply_markup'] = (new Keyboard(['➡ Продолжить', '❌ Отмена']))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        $data['text'] = 'Продолжить:';
                        if ($text !== '') {
                            $data['text'] = 'Выберите вариант, на клавиатуре:';
                        }

                        $result = Request::sendMessage($data);
                        break;
                    }
                    if ($text === '➡ Продолжить') {
                        $notes['confirm'] = $text;
                        $text = '';
                    } else {
                        return $this->telegram->executeCommand('cancel');
                    }
                case 1:
                    if ($text === '' || $notes['confirm'] == '➡ Продолжить') {
                        $notes['state'] = 1;
                        $this->conversation->update();

                        $data['reply_markup'] = (new Keyboard([$user->getFirstName() . ' ' . $user->getLastName(), 'Отмена']))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        $data['text'] = 'Ваше имя:';
                        if (empty($text)) {
                            $result = Request::sendMessage($data);
                            break;
                        }
                    }
                    $notes['name'] = $text;
                    $text = '';
                // no break
                case 2:

                    $delivery = Delivery::find()->all();
                    $deliveryList = [];
                    $keyboards = [];
                    foreach ($delivery as $item) {
                        $deliveryList[$item->id] = $item->name;
                        $keyboards[] = new KeyboardButton($item->name);
                    }
                    $keyboards[] = new KeyboardButton('❌ Отмена');
                    $keyboards = array_chunk($keyboards, 2);


                    $buttons = (new Keyboard(['keyboard' => $keyboards]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);


                    if ($text === '' || !in_array($text, $deliveryList, true)) {
                        $notes['state'] = 2;
                        $this->conversation->update();

                        $data['reply_markup'] = $buttons;

                        $data['text'] = 'Выберите вариант доставки:';
                        if ($text !== '') {
                            $data['text'] = 'Выберите вариант доставки, на клавиатуре:';
                        }

                        $result = Request::sendMessage($data);
                        break;
                    }

                    $notes['delivery'] = $text;
                    $notes['delivery_id'] = array_search($text, $deliveryList);
                // no break
                case 3:

                    $payments = Payment::find()->all();
                    $paymentList = [];
                    $keyboards = [];
                    foreach ($payments as $k => $item) {
                        $paymentList[$item->id] = $item->name;
                        $keyboards[] = new KeyboardButton(['text' => $item->name]);
                    }
                    $keyboards[] = new KeyboardButton('❌ Отмена');
                    $keyboards = array_chunk($keyboards, 2);

                    $buttons = (new Keyboard(['keyboard' => $keyboards]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    if ($text === '' || !in_array($text, $paymentList, true)) {
                        $notes['state'] = 3;
                        $this->conversation->update();

                        $data['reply_markup'] = $buttons;

                        $data['text'] = 'Выберите вариант оплаты:';
                        if ($text !== '') {
                            $data['text'] = 'Выберите вариант оплаты, на клавиатуре:';
                        }

                        $result = Request::sendMessage($data);
                        break;
                    }

                    $notes['payment'] = $text;
                    $notes['payment_id'] = array_search($text, $paymentList);
                // no break
                case 4:
                    if ($message->getContact() === null) {
                        $notes['state'] = 4;
                        $this->conversation->update();

                        $data['reply_markup'] = (new Keyboard(
                            (new KeyboardButton('📞 Оставить контакты'))->setRequestContact(true),
                            new KeyboardButton('❌ Отмена')
                        ))
                            ->setOneTimeKeyboard(true)
                            ->setResizeKeyboard(true)
                            ->setSelective(true);

                        $data['text'] = 'Ваши контактные данные:';

                        $result = Request::sendMessage($data);
                        break;
                    }

                    $notes['phone_number'] = $message->getContact()->getPhoneNumber();

                // no break
                case 5:
                    $this->conversation->update();
                    $content = '*✅ Ваш заказ успешно оформлен*' . PHP_EOL . PHP_EOL;
                    $order = Order::find()->where(['client_id' => $user_id, 'checkout' => 0])->one();
                    if ($order) {
                        $products = $order->products;
                        if ($products) {
                            foreach ($products as $product) {
                                $content .= '*' . $product->name . ' (' . $product->quantity . ' шт.)*: ' . $this->number_format($product->price) . ' грн.' . PHP_EOL;
                            }
                        }
                    }

                    unset($notes['state']);
                    //foreach ($notes as $k => $v) {
                    //    $content .= PHP_EOL . '*' . ucfirst($k) . '*: ' . $v;
                    //}

                    $content .= PHP_EOL . '*Имя*: ' . $notes['name'];
                    $content .= PHP_EOL . '*Телефон*: ' . $notes['phone_number'] . PHP_EOL;

                    $content .= PHP_EOL . '*Доставка*: ' . $notes['delivery'];
                    $content .= PHP_EOL . '*Оплата*: ' . $notes['payment'];

                    $content .= PHP_EOL . PHP_EOL . 'Сумма заказа: *' . $this->number_format($order->total_price) . '* грн.';

                    $order->delivery = $notes['delivery'];
                    $order->payment = $notes['payment'];
                    $order->delivery_id = $notes['delivery_id'];
                    $order->payment_id = $notes['payment_id'];
                    $order->checkout = 1;
                    $order->save();

                    $data['parse_mode'] = 'Markdown';
                    $data['reply_markup'] = $this->homeKeyboards();
                    $data['text'] = $content;
                    $result = Request::sendMessage($data);


                    if ($result->isOk()) {
                        $inlineKeyboards[] = [
                            new InlineKeyboardButton(['text' => Yii::t('telegram/command', 'BUTTON_PAY', $this->number_format($order->total_price)), 'callback_data' => "payment/{$order->id}"]),
                        ];
                        $data['reply_markup'] = new InlineKeyboard([
                            'inline_keyboard' => $inlineKeyboards
                        ]);
                        $data['text'] = '🙍🏼‍♀ Наш менеджер свяжеться с вами!';
                        $result = Request::sendMessage($data);
                    }

                    $this->conversation->stop();
                    break;
            }
        } else {
            $data['text'] = 'Уже оформлен!';
            $data['reply_markup'] = $this->startKeyboards();

            $result = Request::sendMessage($data);
        }
        return $result;
    }


}
