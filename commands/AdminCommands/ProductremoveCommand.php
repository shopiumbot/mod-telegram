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
class ProductremoveCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'productremove';

    /**
     * @var string
     */
    protected $description = 'Удаление товара';

    /**
     * @var string
     */
    protected $usage = '/productremove <id>';

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
    public $product_id;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $chat = $message->getChat();
            $user = $callbackQuery->getFrom();
            parse_str($callbackQuery->getData(), $params);
            echo $this->product_id.PHP_EOL;
print_r($params);die;
        } else {
            $message = $this->getMessage();
            $chat = $message->getChat();
            $user = $message->getFrom();

        }
        $chat_id = $chat->getId();
        $user_id = $user->getId();
        $text = trim($message->getText(false));
        $data['chat_id'] = $chat_id;
        //if ($text === '') {
        //    $text = Yii::t('telegram/command','USAGE',$this->getUsage());
        //    $data['text']=$text;
       //     return Request::sendMessage($data);
       // }


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
                if (!is_numeric($text)) {

                }
                /*if ($text === '' || !in_array($text, ['Да', 'Нет'], true)) {
                    $notes['state'] = 0;
                    $notes['confirm']=false;
                    $this->conversation->update();


                    $data['reply_markup'] = (new Keyboard(['Да', 'Нет']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Укажите ID товара';
                    if ($text !== '') {
                        $data['text'] = 'ID товара должен быть числом:';
                    }


                    $result = Request::sendMessage($data);
                    break;
                }
                $notes['confirm'] = true;

                $text = '';*/



            // no break
            case 1:

                if ($notes['confirm']) {
                    $notes['state'] = 1;
                    if (!is_numeric($text)) {
                        $this->conversation->update();

                        $data['text'] = 'Укажите ID товара';
                        if ($text !== '') {
                            $data['text'] = 'ID товара должен быть числом:';
                        }
                        $result = Request::sendMessage($data);
                        break;
                    } else {

                        $this->conversation->update();
                        //  echo $text;
                        $product = Product::findOne($text);

                        if ($product) {
                            $data['text'] = 'Товар найден';
                            $data['reply_markup'] = (new Keyboard(['Отмена']))
                                ->setResizeKeyboard(true)
                                ->setOneTimeKeyboard(true)
                                ->setSelective(true);
                            $result = Request::sendMessage($data);
                            break;
                        } else {
                            $data['text'] = 'Товар не найден';
                            $result = Request::sendMessage($data);

                        }
                    }
                }


                $notes['product'] = $product;
                $notes['id'] = $text;

                $text = '';
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
