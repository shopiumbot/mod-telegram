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
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;
use core\modules\shop\models\Product;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/search" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class SearchCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'search';

    /**
     * @var string
     */
    protected $description = 'Survery for bot users';

    /**
     * @var string
     */
    protected $usage = '/search';

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
        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
        ];

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
        $notes['status'] = false;
        $result = Request::emptyResponse();

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === '') {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Введите название товара или артикул:';
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    if ($text !== '') {
                        $data['text'] = 'Введите название товара или артикул:';
                    }
                    $result = Request::sendMessage($data);
                    if($result->getOk()){
                        echo $text;
                       // print_r($result->getResult());
                    }
                    break;
                }

                $notes['query'] = $text;
                $text = '';

            // no break
            case 1:


                    $query = Product::find()->sort()->published()->groupBy(Product::tableName() . '.`id`');
                    $query->applySearch($notes['query']);

                    $resultQuery = $query->all();
                    if ($resultQuery) {
                        $notes['state'] = 1;
                        $notes['status'] = true;
                        $this->conversation->update();
                        $inline_keyboard = new InlineKeyboard([
                            [
                                'text' => '👉 ' . Yii::t('shop/default', 'SEARCH_RESULT', [
                                        'query' => $notes['query'],
                                        'count' => count($resultQuery),
                                    ]),
                                'url' => 'https://yii2.pixelion.com.ua/search?q=' . $notes['query'],

                            ],
                        ]);

                        $data = [
                            'chat_id' => $chat_id,
                            'parse_mode' => 'HTML',
                            'text' => Yii::t('shop/default', 'SEARCH_RESULT', [
                                'query' => $text,
                                'count' => count($resultQuery),
                            ]),
                            'reply_markup' => $inline_keyboard,
                        ];
                    } else {
                        $notes['status'] = false;
                        $notes['state'] = 1;
                        $this->conversation->update();
                        $data = [
                            'chat_id' => $chat_id,
                            'parse_mode' => 'HTML',
                            'text' => Yii::t('shop/default', 'SEARCH_RESULT', [
                                'query' => $text,
                                'count' => 0,
                            ]),
                            'reply_markup' => $this->homeKeyboards(),
                        ];
                        $text = '';
                       // $this->conversation->cancel();
                    }

                    $result = Request::sendMessage($data);
                    break;
     
                $this->conversation->stop();
                $notes['query'] = $text;

                break;
        }

        return $result;
    }
}
