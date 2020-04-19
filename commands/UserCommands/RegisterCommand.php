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
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/register" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class RegisterCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'register';

    /**
     * @var string
     */
    protected $description = 'Registration for bot users';

    /**
     * @var string
     */
    protected $usage = '/register';

    /**
     * @var string
     */
    protected $version = '0.1.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * @var bool
     */
    protected $private_only = true;
    protected $enabled = false;

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

        $result = Request::emptyResponse();


        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === '') {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Ваша почта:';
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                    //$result = Request::sendMessage($data);
                    $result = Yii::$app->telegram->sendMessage($data);
                    break;
                }

                $notes['email'] = $text;
                $text = '';

            // no break
            case 1:
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = 'Телефон:'.$message->getContact()->getPhoneNumber();

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['phone_number2'] = $text;
                $text = '';

            // no break
            case 2:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['text'] = 'Пароль:';
                    if ($text !== '') {
                        $data['text'] = 'Type your age, must be a number:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['password'] = $text;
                $text = '';

            // no break
            case 3:
                if ($text === '' || !in_array($text, ['Мужской', 'Женский'], true)) {
                    $notes['state'] = 3;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard(['Мужской', 'Женский']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Ваш пол:';
                    if ($text !== '') {
                        $data['text'] = 'Select your gender, choose a keyboard option:';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['gender'] = $text;

            // no break
           /* case 4:
                if ($message->getLocation() === null) {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard(
                        (new KeyboardButton('Share Location'))->setRequestLocation(true)
                    ))
                        ->setOneTimeKeyboard(true)
                        ->setResizeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Share your location:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['longitude'] = $message->getLocation()->getLongitude();
                $notes['latitude'] = $message->getLocation()->getLatitude();

            // no break*/
            case 4:
                if ($message->getPhoto() === null) {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $data['text'] = 'Insert your picture:';

                    $result = Request::sendMessage($data);
                    break;
                }

                /** @var PhotoSize $photo */
                $photo = $message->getPhoto()[0];
                $notes['photo_id'] = $photo->getFileId();

            // no break
            case 5:
                if ($message->getContact() === null) {
                    $notes['state'] = 5;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard(
                        (new KeyboardButton('Share Contact'))->setRequestContact(true)
                    ))
                        ->setOneTimeKeyboard(true)
                        ->setResizeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Share your contact information:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['phone_number'] = $message->getContact()->getPhoneNumber();

            // no break
            case 6:
                $this->conversation->update();
                $out_text = '/Survey result:' . PHP_EOL;
                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                }

                $data['photo'] = $notes['photo_id'];
                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['caption'] = $out_text;
                $this->conversation->stop();

                $result = Request::sendPhoto($data);
                break;
        }

        return $result;
    }
}
