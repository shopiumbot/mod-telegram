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
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use panix\mod\shop\models\Category;
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
    protected $version = '1.0';
    /**
     * The Google API Key from the command config
     *
     * @var string
     */
    private $category_id;
    private $page;
    public $private_only = true;

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
        if (($this->category_id = trim($this->getConfig('category_id'))) === '') {
            $this->category_id = 1;
        }

        // $preg = preg_match('/^(\/catalog)\s([0-9]+)/', trim($message->getText()), $match);
        //if ($message->getText() == '/catalog' || $preg) {
        // $id = (isset($match[1])) ? $match[1] : 1;
        $root = Category::findOne($this->category_id);
        $categories = $root->children()->all();


        $inlineKeyboards = [];
        if ($categories) {

            foreach ($categories as $category) {

                $count = $category->countItems;
                if ($count) {
                    $child = $category->children()->count();
                    if ($child) {
                        $inlineKeyboards[] = [
                            new InlineKeyboardButton([
                                'text' => '📂 ' . $category->name,
                                'callback_data' => 'getCatalog ' . $category->id
                            ])
                        ];
                    } else {
                        //  $inlineKeyboards[] = [new InlineKeyboardButton(['text' => '📄 ' . $category->name . ' (' . $count . ')', 'callback_data' => 'getCatalogList ' . $category->id])];
                        $inlineKeyboards[] = [
                            new InlineKeyboardButton([
                                'text' => '📄 ' . $category->name . ' (' . $count . ')',
                                // 'callback_data' => 'getCatalogList/' . $category->id
                                'callback_data' => 'query=getCatalogList&category_id=' . $category->id
                            ])
                        ];
                    }
                }

            }
        }

        $data = [
            'chat_id' => $chat_id,
            'text' => 'Выберите раздел:',
            'reply_markup' => new InlineKeyboard([
                'inline_keyboard' => $inlineKeyboards
            ]),
        ];


        $dataCatalog['text'] = '⬇ Каталог продукции';
        $dataCatalog['chat_id'] = $chat_id;
        $dataCatalog['reply_markup'] = $this->catalogKeyboards();
        $buttonsResponse = Request::sendMessage($dataCatalog);

        $result = $data;

        return Request::sendMessage($result);

    }


}
