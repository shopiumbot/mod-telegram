<?php

namespace shopium\mod\telegram\commands\UserCommands;


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\cart\models\Delivery;
use shopium\mod\cart\models\Payment;
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
    protected $version = '1.1.0';

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
            $order = Order::find()->where(['user_id' => $user_id, 'checkout' => 0])->one();
        }


        $data['chat_id'] = $chat_id;
        $text = trim($message->getText(true));


        /*if (!$order || !$order->getProducts()->count()) {
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
            if (!$order->getProducts()->count()) {
                // $data['reply_markup'] = $this->startKeyboards();
                //  return $this->notify(Yii::$app->settings->get('telegram', 'empty_cart_text'),'info');


                $data_edit = [
                    'chat_id' => $chat_id,
                    'message_id' => $message->getMessageId(),
                    'text' => Yii::t('cart/default','CART_EMPTY'),
                ];
                return Request::editMessageText($data_edit);


            }
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
                    username:
                    if ($text == '⬅ Назад') {
                        $text = '';
                    }
                    if ($text === '' || $notes['confirm'] === '➡ Продолжить') {
                        $notes['state'] = 1;
                        $this->conversation->update();

                        $data['reply_markup'] = (new Keyboard([$user->getFirstName() . ' ' . $user->getLastName(), '❌ Отмена']))
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
                    delivery:
                    if ($text === '⬅ Назад') {
                        $text = '';
                        goto username;
                    }
                    $delivery = Delivery::find()->all();
                    $deliveryList = [];
                    $deliverySystemList = [];
                    $keyboards = [];
                    foreach ($delivery as $item) {
                        $deliveryList[$item->id] = $item->name;

                        if($item->system){
                            $deliverySystemList[$item->system] = $item->name;
                        }

                        $keyboards[] = new KeyboardButton($item->name);
                    }




                    $keyboards = array_chunk($keyboards, 2);
                    $keyboards[] = [
                        new KeyboardButton('⬅ Назад'),
                        new KeyboardButton('❌ Отмена')
                    ];


                    $buttons = (new Keyboard(['keyboard' => $keyboards]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);


                    /*if ($text === '' || !in_array($text, $deliverySystemList, true)) {
                        $data['reply_markup'] = $buttons;

                        $data['text'] = 'Выберите вариант доставки:';
                        if ($text !== '') {
                            $data['text'] = 'Выберите вариант доставки, на клавиатуре:';
                        }
                        $result = Request::sendMessage($data);
                    }*/


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
                    if($deliverySystemList){
                        $notes['delivery_system'] = array_search($text, $deliverySystemList);
                    }


                    if (isset($notes['delivery_system'])) {
                        if($notes['delivery_system'] == 'novaposhta'){
                            $text = '';
                            goto system_novaposhta;
                        }
                    }

                case "2.1":
                    system_novaposhta:

                    $delivery = Delivery::findOne($notes['delivery_id']);
                    if ($text === 'Доставка на адрес') {
                        $data['text'] = 'Введите адрес:';
                        $result = Request::sendMessage($data);
                        break;
                    }

                    $notes['state'] = "2.1";
                    $this->conversation->update();
                    $keyboards = [];
                    $keyboards[] = [
                        new KeyboardButton('Доставка на адрес'),
                        new KeyboardButton('Доставка на отделение')
                    ];
                    $keyboards[] = [
                        new KeyboardButton('⬅ Назад'),
                        new KeyboardButton('❌ Отмена')
                    ];
                    $buttons = (new Keyboard(['keyboard' => $keyboards]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);
                    $data['reply_markup'] = $buttons;
                    $data['text'] = 'Выберите вариант доставки:';
                    if ($text !== '') {
                        $data['text'] = 'Выберите вариант доставки, на клавиатуре:';
                    }

                    $result = Request::sendMessage($data);
                    $data['text'] = json_encode($notes);
                    $result2 = Request::sendMessage($data);

                    $this->conversation->stop();
                    break;

                // no break
                case 3:
                    payment:
                    if ($text === '⬅ Назад') {
                        $text = '';
                        goto delivery;
                    }
                    $payments = Payment::find()->all();
                    $paymentList = [];
                    $keyboards = [];
                    foreach ($payments as $k => $item) {
                        $paymentList[$item->id] = $item->name;
                        $keyboards[] = new KeyboardButton(['text' => $item->name]);
                    }
                    $keyboards = array_chunk($keyboards, 2);
                    $keyboards[] = [
                        new KeyboardButton('⬅ Назад'),
                        new KeyboardButton('❌ Отмена')
                    ];
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
                    contact:
                    if ($text === '⬅ Назад') {
                        $text = '';
                        goto payment;
                    }
                    if ($message->getContact() === null) {
                        $notes['state'] = 4;
                        $this->conversation->update();

                        $keyboards = [
                            [
                                (new KeyboardButton(Yii::t('telegram/default','SET_CONTACT')))->setRequestContact(true)],
                            [
                                new KeyboardButton('⬅ Назад'),
                                new KeyboardButton('❌ Отмена')
                            ]
                        ];
                        $buttons = (new Keyboard(['keyboard' => $keyboards]))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);


                        $data['reply_markup'] = $buttons;

                        $data['text'] = 'Ваши контактные данные:';

                        $result = Request::sendMessage($data);
                        break;
                    }

                    $notes['phone_number'] = $message->getContact()->getPhoneNumber();

                // no break
                case 5:
                    $this->conversation->update();
                    $titleClient = '*✅ Ваш заказ успешно оформлен*' . PHP_EOL . PHP_EOL;
                    $order = Order::find()->where(['user_id' => $user_id, 'checkout' => 0])->one();
                    $content = '';
                    if ($order) {
                        $products = $order->products;
                        if ($products) {
                            foreach ($products as $product) {
                                $content .= '*' . $product->name . ' (' . $product->quantity . ' '.Yii::t('shop/Product','UNIT_THING').')*: ' . $this->number_format($product->price) . ' грн.' . PHP_EOL;
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

                    //$order->delivery = $notes['delivery'];
                    //$order->payment = $notes['payment'];
                    $order->delivery_id = $notes['delivery_id'];
                    $order->payment_id = $notes['payment_id'];
                    $order->user_phone = $notes['phone_number'];
                    $order->status_id = 1;
                    $order->checkout = 1;
                    $order->save(false);


                    //$test = $order->sendAdminEmail();
                    $titleOwner = '*✅ Новый заказ '.CMS::idToNumber($order->id).'*' . PHP_EOL . PHP_EOL;
                    $this->telegram->getAdminList();
                    foreach ($this->telegram->getAdminList() as $admin){
                        $data2['chat_id'] = $admin;
                        $data2['parse_mode'] = 'Markdown';
                        $data2['text'] = $titleOwner.$content;
                        $result2 = Request::sendMessage($data2);
                    }




                    $data['parse_mode'] = 'Markdown';
                    $data['reply_markup'] = $this->homeKeyboards();
                    $data['text'] = $titleClient.$content;
                    $result = Request::sendMessage($data);


                    if ($result->isOk()) {
                        $inlineKeyboards[] = [
                            new InlineKeyboardButton(['text' => Yii::t('telegram/default', 'BUTTON_PAY', $this->number_format($order->total_price)), 'callback_data' => "payment/{$order->id}"]),
                        ];
                        $data['reply_markup'] = new InlineKeyboard([
                            'inline_keyboard' => $inlineKeyboards
                        ]);
                        $data['text'] = '🙍🏼‍♀ Наш менеджер свяжеться с вами!‍';
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
