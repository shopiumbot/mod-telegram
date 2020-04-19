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
use core\modules\shop\models\Category;
use core\modules\shop\models\Product;
use core\modules\shop\models\ProductType;
use shopium\mod\telegram\components\AdminCommand;
use Yii;

/**
 * User "/productadd" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class ProductaddCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'productadd';

    /**
     * @var string
     */
    protected $description = 'Добавление товара';

    /**
     * @var string
     */
    protected $usage = '/productadd';

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

                $model = Category::find()->excludeRoot()->all();
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
                if ($text === '') {
                    $notes['state'] = 3;
                    $this->conversation->update();
                    $data['text'] = 'Название товара:';
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $result = Request::sendMessage($data);

                    break;
                }

                $notes['name'] = $text;
                $text = '';
            // no break
            case 4:

                $text = '';
            // no break
            case 5:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 5;
                    $this->conversation->update();

                    $data['text'] = 'Цена:';
                    if ($text !== '') {
                        $data['text'] = 'Цена должна быть числом:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['price'] = $text;
                $text = '';

            // no break
            case 6:
                if ($message->getPhoto() === null) {
                    $notes['state'] = 6;
                    $this->conversation->update();

                    $data['text'] = 'Перетащите изображение:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $message_type = $message->getType();


                $doc = $message->{'get' . ucfirst($message_type)}();

                // For photos, get the best quality!
                ($message_type === 'photo') && $doc = end($doc);

                $file_id = $doc->getFileId();
                $file = Request::getFile(['file_id' => $file_id]);
                if ($file->isOk()) {
                    $filePath = $this->telegram->getDownloadPath().DIRECTORY_SEPARATOR.$file->getResult()->file_path;
                    if(!file_exists($filePath)){
                        $download = Request::downloadFile($file->getResult());
                    }

                    $data['text'] = $message_type . ' file is located at: ' . $filePath;
                } else {
                    $data['text'] = 'Ошибка загрузки файла.';
                }
                $r=Request::sendMessage($data);

                /** @var PhotoSize $photo */
                $photo = $message->getPhoto()[0];
                $notes['image'] = $photo->getFileId();
                $notes['image_id'] = $file_id;

            // no break
            case 6:
                $this->conversation->update();
                $content = '✅ Товар успешно добавлен' . PHP_EOL;

                $product = new Product;

                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $content .= PHP_EOL . '<strong>' . ucfirst($k) . '</strong>: ' . $v;
                }

                $product->type_id = $notes['type_id'];
                $product->name = $notes['name'];
                $product->slug = CMS::slug($product->name);
                $product->price = $notes['price'];

                $product->save(false);



                if (true) {
                    // Авто добавление в предков категории
                    // Нужно выбирать в админки самую последнию категории по уровню.
                    $category = Category::findOne($notes['category_id']);
                    $categories = [];
                    if ($category) {
                        $tes = $category->ancestors()->excludeRoot()->all();
                        foreach ($tes as $cat) {
                            $categories[] = $cat->id;
                        }

                    }
                    $categories = array_merge($categories,[]);
                } else {
                    $categories = [];
                }

                $product->setCategories($categories, $notes['category_id']);

                $file = Request::getFile(['file_id' => $notes['image_id']]);
                if ($file->isOk()) {
                    $image= $product->attachImage($this->telegram->getDownloadPath().DIRECTORY_SEPARATOR.$file->getResult()->file_path);

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
