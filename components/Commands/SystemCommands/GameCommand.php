<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;


use core\modules\shop\models\Product;
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
use shopium\mod\cart\models\OrderProduct;
use shopium\mod\cart\models\OrderProductTemp;
use shopium\mod\cart\models\OrderTemp;
use shopium\mod\cart\models\Payment;
use shopium\mod\telegram\components\SystemCommand;
use shopium\mod\cart\models\Order;
use Yii;

/**
 * User "/game" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class GameCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'game';

    /**
     * @var string
     */
    protected $description = 'game';

    /**
     * @var string
     */
    protected $usage = '/game';

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
            parse_str($callbackQuery->getData(), $params);
        } else {
            $message = $this->getMessage();
            $chat = $message->getChat();
            $user = $message->getFrom();
        }

        $chat_id = $chat->getId();
        $user_id = $user->getId();
        // $this->order = OrderTemp::findOne(['id' => $user_id]);
        $data['chat_id'] = $chat_id;
        $text = trim($message->getText(true));


        //Preparing Response

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


        $gamesList = ['🎲 Кости', '🏀 Баскетбол', '🎯 Дартс'];
        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === '' || !in_array($text, array_merge($gamesList, [$this->keyword_cancel]), true)) {
                    $notes['state'] = 0;
                    $this->conversation->update();
                    $keyboards=[];
                    $keyboards[] = [
                        new KeyboardButton(['text' => $gamesList[0]]),
                        new KeyboardButton(['text' => $gamesList[1]]),
                        new KeyboardButton(['text' => $gamesList[2]]),
                    ];

                    $keyboards[] = [
                        new KeyboardButton(['text' => $this->keyword_cancel]),
                    ];
                   // $ss[]=[$this->keyword_cancel];
                    $data['reply_markup'] = (new Keyboard([
                        'keyboard' => $keyboards
                    ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true)->setSelective(true);

                    $data['text'] = 'Продолжить:';
                    if ($text !== '') {
                        $data['text'] = 'Выберите вигру:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                if($text == $gamesList[0]){
                    $notes['state'] = 'game_die';
                  //  goto game_die;
                }elseif($text == $gamesList[1]){
                    //goto game_basketball;
                }elseif($text == $gamesList[2]){
                    //goto game_darts;
                }
                $notes['gaming'] = $text;
                $text='';
            case 'game_die':
                game_die:
                if ($text === '' || !in_array($text, [1,2,3,4,5,6], true)) {
                    $notes['state'] = 'game_die';
                    $notes['gaming'] = $gamesList[0];
                    $this->conversation->update();
                    $keyboards = [
                        [
                            new KeyboardButton(['text' => 1]),
                            new KeyboardButton(['text' => 2]),
                            new KeyboardButton(['text' => 3]),
                        ],
                        [
                            new KeyboardButton(['text' => 4]),
                            new KeyboardButton(['text' => 5]),
                            new KeyboardButton(['text' => 6]),
                        ],
                        [
                            new KeyboardButton(['text' => $this->keyword_cancel])
                        ]
                    ];
                    $buttons = (new Keyboard(['keyboard' => $keyboards]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['reply_markup'] = $buttons;
                    $data['text'] = 'Выберите вариант:';
                    if ($text !== '') {
                        $data['text'] = 'Загадайте чисто от 1 до 6:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['selected'] = $text;
                $data2['chat_id']=$chat_id;
                $data2['text']='🎲';
                $result2 = Request::sendMessage($data2);
                $text='';
            case 2:
                $this->conversation->update();
                $titleClient = '*✅ Ваш заказ успешно оформлен*' . PHP_EOL . PHP_EOL;
                // $orderTemp = OrderTemp::findOne($user_id);

                $content = '';


                $titleOwner = '*✅ Новый заказ ' . CMS::idToNumber($o->id) . '*' . PHP_EOL . PHP_EOL;
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
                    $system = ($o->paymentMethod) ? $o->paymentMethod->system : 0;
                    $inlineKeyboards[] = [
                        new InlineKeyboardButton([
                            'text' => Yii::t('telegram/default', 'BUTTON_PAY', [
                                'price'=>$this->number_format($o->total_price),
                                'currency'=>Yii::$app->currency->active['symbol']
                            ]),
                            'callback_data' => "query=orderPay&id={$o->id}&system={$system}"
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

        if ($result->isOk()) {
            $db = DB::insertMessageRequest($result->getResult());
        }
        return $result;
    }


}
