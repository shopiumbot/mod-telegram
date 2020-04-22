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
use core\modules\shop\models\Category;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/catalog" command
 *
 * Display an inline keyboard with a few buttons.
 */
class CatalogCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'catalog';

    /**
     * @var string
     */
    protected $description = 'Каталог продукции';

    /**
     * @var string
     */
    protected $usage = '/catalog <id>';

    /**
     * @var string
     */
    protected $version = '1.1';
    /**
     * The Google API Key from the command config
     *
     * @var string
     */
    public $id;

    public $private_only = true;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $update = $this->getUpdate();

        $isCallback = false;
        if ($update->getCallbackQuery()) {
            $isCallback = true;
            $callbackQuery = $update->getCallbackQuery();

            $message = $callbackQuery->getMessage();
            $chat = $message->getChat();
            $user = $callbackQuery->getFrom();
        } else {
            $message = $this->getMessage();
            $chat = $message->getChat();
            $user = $message->getFrom();
        }

        $chat_id = $chat->getId();
        $user_id = $user->getId();

        if (($this->id = trim($this->getConfig('id'))) === '') {
            $this->id = 1;
        }

        $root = Category::findOne($this->id);
        $categories = $root->children()->all();


        $keyboards = [];
        if ($categories) {
            foreach ($categories as $category) {
                $count = $category->countItems;

                $child = $category->children()->count();
                if ($child) {
                    $keyboards[] = [
                        new InlineKeyboardButton([
                            'text' => '📂 ' . $category->name,
                            'callback_data' => 'query=openCatalog&id=' . $category->id
                        ])
                    ];
                } else {
                    if ($count) {
                        $keyboards[] = [
                            new InlineKeyboardButton([
                                'text' => $category->name . ' (' . $count . ')',
                                // 'callback_data' => 'getCatalogList/' . $category->id
                                'callback_data' => 'query=getCatalogList&category_id=' . $category->id
                            ])
                        ];
                    }
                }
            }
        } else {
            return $this->notify('В каталоге нет продукции', 'info');
        }


        if ($isCallback) {
            $back = $root->parent()->one();
            if ($back) {
                $keyboards[] = [
                    new InlineKeyboardButton([
                        'text' => '↩ ' . $back->name,
                        'callback_data' => 'query=openCatalog&id=' . $back->id
                    ])];
            }
            $dataEdit['chat_id'] = $chat_id;
            $dataEdit['message_id'] = $message->getMessageId();
            $dataEdit['reply_markup'] = new InlineKeyboard([
                'inline_keyboard' => $keyboards
            ]);
            return Request::editMessageReplyMarkup($dataEdit);
        } else {
            $data = [
                'chat_id' => $chat_id,
                'text' => 'Выберите раздел:',
                'reply_markup' => new InlineKeyboard([
                    'inline_keyboard' => $keyboards
                ]),
            ];


            $dataCatalog['text'] = '⬇ Каталог продукции';
            $dataCatalog['chat_id'] = $chat_id;
            $dataCatalog['reply_markup'] = $this->catalogKeyboards();
            $buttonsResponse = Request::sendMessage($dataCatalog);

            $result = $data;

        }


        return Request::sendMessage($result);


    }


}
