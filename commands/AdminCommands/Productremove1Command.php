<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace shopium\mod\telegram\commands\AdminCommands;


use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use core\modules\shop\models\Product;
use shopium\mod\telegram\components\AdminCommand;
use Yii;

/**
 * User "/productremove" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class Productremove1Command extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'productremove1';

    /**
     * @var string
     */
    protected $description = 'Удаление товара';

    /**
     * @var string
     */
    protected $usage = '/productremove1 <id>';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;


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
        $data['chat_id'] = $chat_id;

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
        $product = null;
        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:

                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 0;
                    $this->conversation->update();
                    $data['text'] = 'Укажите ID товара';
                    if ($text !== '') {
                        $data['text'] = 'ID товара должен быть числом:';
                    }
                    $result = Request::sendMessage($data);
                    break;
                }
                if ($text !== '' || is_numeric($text)){
                    $notes['state'] = 0;
                    $this->conversation->update();
                   // echo $text;
                    $product = Product::findOne($text);

                    if ($product) {
                        $data['text'] = 'Товар не найден';
                        $data['reply_markup'] = (new Keyboard(['Отмена']))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);
                        $result = Request::sendMessage($data);
                        break;
                    }
                }

                $notes['product'] = $product;
                $notes['id'] = $text;

                $text = '';
            // no break

            case 1:


                if ($text === '' || !in_array($text, ['Yes', 'No'], true)) {
                    $notes['state'] = 1;
                    $this->conversation->update();


                    $data['reply_markup'] = (new Keyboard(['Yes', 'No']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Подтвердите действие:';
                    if ($text !== '') {
                        $data['text'] = 'Выберите действие, на клавиатуре:';
                    }


                    $result = Request::sendMessage($data);
                    break;
                }

                if ($text == 'No') {
                    $notes['bool'] = 'No';
                }
                $notes['bool'] = $text;

            //  $text = '';
            // no break
            case 2:
                $notes['state'] = 2;
                $this->conversation->update();
                $content = '✅ Товар успешно удален' . PHP_EOL;

                $product = new Product;

                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $content .= PHP_EOL . '<strong>' . ucfirst($k) . '</strong>: ' . $v;
                }


                $data['parse_mode'] = 'HTML';
                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['text'] = $content;
                $this->conversation->stop();

                $result = Request::sendMessage($data);
                break;
        }

        return $result;
    }
}
