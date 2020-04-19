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


use Longman\TelegramBot\Commands\AdminCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use core\modules\shop\models\Attribute;
use core\modules\shop\models\Category;
use core\modules\shop\models\Product;
use core\modules\shop\models\ProductType;
use Yii;

/**
 * User "/test" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class TestCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'test';

    /**
     * @var string
     */
    protected $description = 'test';

    /**
     * @var string
     */
    protected $usage = '/test';

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

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:

                $model = ProductType::find()->all();
                $list = [];
                $keyboards = [];
                foreach ($model as $k => $item) {
                    $list[$item->id] = $item->name;
                    $keyboards[] = new KeyboardButton(['text' => $item->name]);
                }
                $keyboards = array_chunk($keyboards, 2);

                $buttons = (new Keyboard(['keyboard' => $keyboards]))
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->setSelective(true);

                if ($text === '' || !in_array($text, $list, true)) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['reply_markup'] = $buttons;

                    $data['text'] = 'Выберите тип товара:';
                    if ($text !== '') {
                        $data['text'] = 'Выберите тип товара, на клавиатуре:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['type'] = $text;
                $notes['type_id'] = array_search($text, $list);
                $text = '';
            // no break
            case 2:

                $model = ProductType::findOne($notes['type_id']);

               $attributes = $model->shopAttributes;
                if($attributes){
                    foreach ($attributes as $a) {
                        $value = $model->getEavAttribute($a->name);


                        if ($a->type == Attribute::TYPE_DROPDOWN) {
                            $addOptionLink = Html::a(Html::icon('add'), '#', [
                                'rel' => $a->id,
                                'data-name' => $a->getIdByName(), //$a->getIdByName()
                                //'data-name' => Html::getInputName($a, $a->name),
                                'onclick' => 'js: return addNewOption($(this));',
                                'class' => 'btn btn-success', // btn-sm mt-2 float-right
                                'title' => Yii::t('shop/admin', 'ADD_OPTION')
                            ]);

                            // . ' ' . Yii::t('shop/admin', 'ADD_OPTION')
                        } else
                            $addOptionLink = null;

                        $error = '';
                        $inputClass = '';

                        if ($a->required && array_key_exists($a->name, $model->getErrors())) {
                            $inputClass = 'is-invalid';
                            $error = Html::error($a, $a->name);
                        }

$required=($a->required ? 'required' : '');


$a->title;
                        $a->name;


 $a->renderField($value, $inputClass);











                    }
                }
               /* $list = [];
                $keyboards = [];
                foreach ($model as $k => $item) {
                    $list[$item->id] = $item->name;
                    $keyboards[] = new KeyboardButton(['text' => $item->name]);
                }
                $keyboards = array_chunk($keyboards, 2);

                $buttons = (new Keyboard(['keyboard' => $keyboards]))
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->setSelective(true);
*/
                if ($text === '' || !in_array($text, $list, true)) {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['reply_markup'] = $buttons;

                    $data['text'] = 'Выберите категорию:';
                    if ($text !== '') {
                        $data['text'] = 'Выберите категорию, на клавиатуре:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['category'] = $text;
                $notes['category_id'] = array_search($text, $list);
                $text = '';
            // no break

            case 3:
                $this->conversation->update();
                $content = '✅ Товар успешно добавлен' . PHP_EOL;



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
