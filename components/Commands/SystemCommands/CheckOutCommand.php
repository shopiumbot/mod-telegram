<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\cart\models\Delivery;
use shopium\mod\cart\models\NovaPoshtaArea;
use shopium\mod\cart\models\NovaPoshtaCities;
use shopium\mod\cart\models\NovaPoshtaWarehouses;
use shopium\mod\cart\models\OrderTemp;
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
    protected $version = '1.1.1';

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


        //Preparing Response

        if ($order) {
            if (!$order->getProducts()->count()) {
                // $data['reply_markup'] = $this->startKeyboards();
                //  return $this->notify(Yii::$app->settings->get('telegram', 'empty_cart_text'),'info');


                $data_edit = [
                    'chat_id' => $chat_id,
                    'message_id' => $message->getMessageId(),
                    'text' => $this->settings->empty_cart_text,
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
                    if ($text === '' || !in_array($text, ['➡ Продолжить', static::KEYWORD_CANCEL], true)) {
                        $notes['state'] = 0;
                        $this->conversation->update();

                        $data['reply_markup'] = (new Keyboard(['➡ Продолжить', static::KEYWORD_CANCEL]))
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
                    if ($text == static::KEYWORD_BACK) {
                        $text = '';
                    }
                    if ($text === '' || $notes['confirm'] === '➡ Продолжить') {
                        $notes['state'] = 1;
                        $this->conversation->update();

                        $data['reply_markup'] = (new Keyboard(['👤 ' . $user->getFirstName() . ' ' . $user->getLastName(), '❌ Отмена']))
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
                    if ($text === static::KEYWORD_BACK) {
                        $text = '';
                        goto username;
                    }
                    $delivery = Delivery::find()->all();
                    $deliveryList = [];
                    $keyboards = [];
                    foreach ($delivery as $item) {
                        $deliveryList[$item->id] = $item->name;
                        $keyboards[] = new KeyboardButton($item->name);
                    }
                    $keyboards = array_chunk($keyboards, 2);
                    $keyboards[] = [
                        new KeyboardButton(static::KEYWORD_BACK),
                        new KeyboardButton(static::KEYWORD_CANCEL)
                    ];


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


                case '2.1':
                    delivery_novaposhta:
                    if ($text === static::KEYWORD_BACK) {
                        $text = '';
                        unset($notes['delivery'], $notes['delivery_id']);
                        goto delivery;
                    }
                    //Новая почта
                    $deliverytest = Delivery::findOne((int)$notes['delivery_id']);

                    if ($deliverytest->system) {
                        $keyboards = [];
                        $cityList = [];
                        $model = NovaPoshtaArea::find()
                            //->where(['Area'=>'71508136-9b87-11de-822f-000c2965ae0e'])
                            ->orderBy(['DescriptionRu' => SORT_ASC])
                            ->asArray()
                            // ->limit(60)
                            ->all();
                        // $this->notify(json_encode($model));
                        foreach ($model as $city) {
                            $cityList[$city['Ref']] = ((!empty($city['DescriptionRu'])) ? $city['DescriptionRu'] : $city['Description']);
                            //$cityList[$city['Ref']] = $city['Ref'];
                            $keyboards[] = new KeyboardButton(((!empty($city['DescriptionRu'])) ? $city['DescriptionRu'] : $city['Description']));
                        }
                        $keyboards = array_chunk($keyboards, 2);
                        $keyboards[] = [
                            new KeyboardButton(static::KEYWORD_BACK),
                            new KeyboardButton(static::KEYWORD_CANCEL)
                        ];


                        $buttons = (new Keyboard(['keyboard' => $keyboards]))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);


                        if ($text === '' || !in_array($text, $cityList, true)) {
                            $notes['state'] = '2.1';
                            $this->conversation->update();

                            $data['reply_markup'] = $buttons;
                            $data['text'] = 'Выберите область доставки:';
                            if ($text !== '') {
                                $data['text'] = 'Выберите область доставки, на клавиатуре:';
                            }

                            $result = Request::sendMessage($data);
                            break;
                        }

                        $notes['delivery_area'] = $text;
                        $notes['delivery_area_id'] = array_search($text, $cityList);
                    }


                case '2.2':
                    delivery_novaposhta_city:
                    if ($text === static::KEYWORD_BACK) {
                        $text = '';
                        unset($notes['delivery_area'], $notes['delivery_area_id']);
                        goto delivery_novaposhta;
                    }

                    //if (isset($notes['delivery_city_id'])) {
                    //Новая почта  warehouses
                    $keyboards = [];
                    $citiesList = [];


                    /* $model = NovaPoshtaWarehouses::find()
                         ->where(['CityRef'=>$area['Ref']])
                         ->orderBy(['DescriptionRu' => SORT_ASC])
                         ->asArray()
                         ->limit(50)
                         ->all();*/

                    if (isset($notes['delivery_area_id'])) {
                        $model = NovaPoshtaCities::find()
                            ->where(['Area' => $notes['delivery_area_id'], 'IsBranch' => 1])
                            ->asArray()
                            ->orderBy(['DescriptionRu' => SORT_DESC])
                            ->limit(80)
                            ->all();


                        /*$model = NovaPoshtaWarehouses::find()
                            ->where(['CityRef'=>$model2['Area']])
                            ->orderBy(['DescriptionRu' => SORT_ASC])
                            ->asArray()
                            ->limit(80)
                            ->all();*/

                        //   $this->notify(count($model));

                        foreach ($model as $warehouses) {
                            $citiesList[$warehouses['Ref']] = $warehouses['DescriptionRu'];
                            $keyboards[] = new KeyboardButton($warehouses['DescriptionRu']);
                        }

                        $keyboards = array_chunk($keyboards, 3);
                        $keyboards[] = [
                            new KeyboardButton(static::KEYWORD_BACK),
                            new KeyboardButton(static::KEYWORD_CANCEL)
                        ];


                        $buttons = (new Keyboard(['keyboard' => $keyboards]))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        if ($text === '' || !in_array($text, $citiesList, true)) {
                            $notes['state'] = '2.2';
                            $this->conversation->update();

                            $data['reply_markup'] = $buttons;
                            $data['text'] = 'Выберите город доставки:';
                            if ($text !== '') {
                                $data['text'] = 'Выберите город доставки, на клавиатуре:';
                            }

                            $result = Request::sendMessage($data);
                            break;
                        }

                        $notes['delivery_city'] = $text;
                        $notes['delivery_city_id'] = array_search($text, $citiesList);
                    }
                // no break
                case '2.3':
                    delivery_novaposhta_warehouses:
                    if ($text === static::KEYWORD_BACK) {
                        $text = '';
                        unset($notes['delivery_city'], $notes['delivery_city_id']);
                        goto delivery_novaposhta_city;
                    }

                    //if (isset($notes['delivery_city_id'])) {
                    //Новая почта  warehouses
                    $keyboards = [];
                    $warehousesList = [];


                    if (isset($notes['delivery_city_id'])) {
                        $model = NovaPoshtaWarehouses::find()
                            ->where(['CityRef' => $notes['delivery_city_id'], 'POSTerminal' => 1])
                            ->orderBy(['Number' => SORT_ASC])
                            ->asArray()
                            // ->limit(80)
                            ->all();


                        // $this->notify(count($model).' - '.$notes['delivery_city_id']);

                        foreach ($model as $warehouses) {
                            $warehousesList[$warehouses['Ref']] = '№' . $warehouses['Number'];
                            $keyboards[] = new KeyboardButton('№' . $warehouses['Number']);
                        }

                        $keyboards = array_chunk($keyboards, 4);
                        $keyboards[] = [
                            new KeyboardButton(static::KEYWORD_BACK),
                            new KeyboardButton(static::KEYWORD_CANCEL)
                        ];


                        $buttons = (new Keyboard(['keyboard' => $keyboards]))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        if ($text === '' || !in_array($text, $warehousesList, true)) {
                            $notes['state'] = '2.3';
                            $this->conversation->update();

                            $data['reply_markup'] = $buttons;
                            $data['text'] = 'Выберите номер отделения доставки:';
                            if ($text !== '') {
                                $data['text'] = 'Выберите номер отделения доставки, на клавиатуре:';
                            }

                            $result = Request::sendMessage($data);
                            break;
                        }

                        $notes['delivery_warehouse'] = $text;
                        $notes['delivery_warehouse_id'] = array_search($text, $warehousesList);
                    }
                // no break

                case 3:
                    payment:
                    if ($text === static::KEYWORD_BACK) {
                        $text = '';
                        unset($notes['delivery'], $notes['delivery_id'], $notes['delivery_area'], $notes['delivery_area_id'], $notes['delivery_city'], $notes['delivery_city_id'], $notes['delivery_warehouse'], $notes['delivery_warehouse_id']);
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
                        new KeyboardButton(static::KEYWORD_BACK),
                        new KeyboardButton(static::KEYWORD_CANCEL)
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
                    if ($text === static::KEYWORD_BACK) {
                        $text = '';
                        goto payment;
                    }
                    if ($message->getContact() === null) {
                        $notes['state'] = 4;
                        $this->conversation->update();

                        $keyboards = [
                            [
                                (new KeyboardButton('📞 Оставить контакты'))->setRequestContact(true)],
                            [
                                new KeyboardButton(static::KEYWORD_BACK),
                                new KeyboardButton(static::KEYWORD_CANCEL)
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
                    $phone = $message->getContact()->getPhoneNumber();
                    $notes['phone_number'] = (strpos($phone, '+')) ? $phone : $phone;

                // no break
                case 5:
                    $this->conversation->update();
                    $titleClient = '*✅ Ваш заказ успешно оформлен*' . PHP_EOL . PHP_EOL;
                    $order = OrderTemp::findOne($user_id);
                    $content = '';
                    if ($order) {
                        $products = $order->products;
                        if ($products) {
                            foreach ($products as $product) {
                                $command = '';
                                if ($product->originalProduct) {
                                    $command .= '/product' . $product->product_id;
                                }
                                $content .= '*' . $product->name . '* '.$command.' *(' . $product->quantity . ' шт.)*: ' . $this->number_format($product->price) . ' грн.' . PHP_EOL;
                            }
                        }
                    }

                    unset($notes['state']);
                    //foreach ($notes as $k => $v) {
                    //    $content .= PHP_EOL . '*' . ucfirst($k) . '*: ' . $v;
                    //}
                    if (isset($notes['delivery_city']))
                        $order->city = $notes['delivery_city'];

                    if (isset($notes['delivery_city_id']))
                        $order->city_id = $notes['delivery_city_id'];


                    if (isset($notes['delivery_area']))
                        $order->area = $notes['delivery_area'];

                    if (isset($notes['delivery_area_id']))
                        $order->area_id = $notes['delivery_area_id'];


                    if (isset($notes['delivery_warehouse']))
                        $order->warehouse = $notes['delivery_warehouse'];

                    if (isset($notes['delivery_warehouse_id']))
                        $order->warehouse_id = $notes['delivery_warehouse_id'];


                    $content .= PHP_EOL . '*Имя*: ' . $notes['name'];
                    $content .= PHP_EOL . '*Телефон*: ' . $notes['phone_number'] . PHP_EOL;

                    $content .= PHP_EOL . '🚚 Доставка: *' . $notes['delivery'] . '*' . PHP_EOL;
                    if ($order->area_id && $order->area) {
                        $content .= 'обл. *' . $order->area . '*, ';
                    }
                    if ($order->city_id && $order->city) {
                        $content .= 'г. *' . $order->city . '*' . PHP_EOL;
                    }

                    if ($order->warehouse_id && $order->warehouse) {
                        $warehouse = NovaPoshtaWarehouses::findOne(['Ref' => trim($order->warehouse_id)]);
                        if ($warehouse) {
                            $content .= '*' . $warehouse->DescriptionRu . '*' . PHP_EOL;
                            $order->user_address = $warehouse->DescriptionRu;
                        } else {
                            $content .= 'Отделение: *' . $order->warehouse . '*' . PHP_EOL;
                            $order->user_address = $order->warehouse;
                        }
                    }
                    $content .= PHP_EOL . '💰 Оплата: *' . $notes['payment'] . '*';

                    $content .= PHP_EOL . PHP_EOL . 'Сумма заказа: *' . $this->number_format($order->total_price) . '* грн.';

                    //$order->delivery = $notes['delivery'];
                    //$order->payment = $notes['payment'];
                    $order->delivery_id = $notes['delivery_id'];
                    $order->payment_id = $notes['payment_id'];
                    $order->user_phone = $notes['phone_number'];
                    $order->user_name = $notes['name'];

                    $order->status_id = 1;
                    $order->checkout = 1;
                    $order->save(false);


                    //$test = $order->sendAdminEmail();
                    $titleOwner = '*✅ Новый заказ ' . CMS::idToNumber($order->id) . '*' . PHP_EOL . PHP_EOL;
                    $admins = $this->telegram->getAdminList();
                    foreach ($admins as $admin) {
                        $data2['chat_id'] = $admin;
                        $data2['parse_mode'] = 'Markdown';
                        $data2['text'] = $titleOwner . $content;
                        $result2 = Request::sendMessage($data2);
                    }


                    $data['parse_mode'] = 'Markdown';
                    $data['reply_markup'] = $this->homeKeyboards();
                    $data['text'] = $titleClient . $content;
                    $result = Request::sendMessage($data);
                    if ($result->isOk()) {
                        $db = DB::insertMessageRequest($result->getResult());
                    }

                    if ($result->isOk()) {
                        $system = ($order->paymentMethod) ? $order->paymentMethod->system : 0;
                        $inlineKeyboards[] = [
                            new InlineKeyboardButton([
                                'text' => Yii::t('telegram/command', 'BUTTON_PAY', $this->number_format($order->total_price)),
                                'callback_data' => "query=orderPay&id={$order->id}&system={$system}"
                            ]),
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
        if ($result->isOk()) {
            $db = DB::insertMessageRequest($result->getResult());
        }
        return $result;
    }


}
