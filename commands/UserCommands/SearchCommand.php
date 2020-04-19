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
    protected $description = 'Поиск товаров';

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
        echo $text;
        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === '' || preg_match('/^(\x{1F50E})/iu', $text, $match)) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Введите название товара или артикул:';
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    if ($text !== '') {
                        $data['text'] = 'Введите название товара или артикул:';
                    }
                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['query'] = $text;
                $text = '';

            // no break
            case 1:

                if ($text === '') {
                    $notes['state'] = 1;
                    $query = Product::find()->sort()->published()->groupBy(Product::tableName() . '.`id`');
                    $query->applySearch($notes['query']);

                    $count = $query->count();


                    if ($count) {
                        $buttons[] = [
                            new InlineKeyboardButton([
                                'text' => Yii::t('telegram/command', 'SEARCH_RESULT_TOTAL', [
                                    'count' => $count,
                                ]),
                                'callback_data' => "query=search&string={$notes['query']}"
                            ])
                        ];
                        $data['chat_id'] = $chat_id;
                        $data['parse_mode'] = 'Markdown';
                        $data['text'] = $text;
                        $data['reply_markup'] = $this->catalogKeyboards();

                        $result2 = Request::sendMessage($data);

                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['parse_mode'] = 'Markdown';
                        $data['text'] = Yii::t('telegram/command', 'SEARCH_RESULT', [
                            'query' => '*' . $notes['query'] . '*',
                        ]);

                        $data['reply_markup'] = new InlineKeyboard(['inline_keyboard' => $buttons]);


                        $result = Request::sendMessage($data);


                    } else {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['parse_mode'] = 'Markdown';
                        $data['text'] = Yii::t('shop/default', 'SEARCH_RESULT', [
                            'count' => $count,
                            'query' => '*' . $notes['query'] . '*',
                        ]);
                        $data['reply_markup'] = $this->catalogKeyboards();
                        $result = Request::sendMessage($data);
                    }
                    $notes['status'] = ($count) ? true : false;
                    $this->conversation->update();
                    $this->conversation->stop();


                    break;
                }

                $notes['query'] = $text;

                break;
        }

        return $result;
    }
}
