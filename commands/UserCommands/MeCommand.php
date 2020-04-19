<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Written by Marco Boretto <marco.bore@gmail.com>
 */

namespace shopium\mod\telegram\commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\File;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Entities\UserProfilePhotos;
use Longman\TelegramBot\Request;
use Yii;

/**
 * User "/me" command
 */
class MeCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'me';
    protected $description = 'Информация обо мне';
    protected $usage = '/me';
    protected $version = '1.0.1';
    protected $public = true;
    public $enabled = true;
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();

        $from = $message->getFrom();
        $user_id = $from->getId();
        $chat_id = $message->getChat()->getId();
        $message_id = $message->getMessageId();

        $data = [
            'chat_id' => $chat_id,
            'reply_to_message_id' => $message_id,
        ];

        //Send chat action
        Request::sendChatAction([
            'chat_id' => $chat_id,
            'action' => 'typing',
        ]);

        $caption = sprintf(
            'ID: %d' . PHP_EOL .
            'Имя: %s %s' . PHP_EOL .
            'Username: %s' . PHP_EOL .
            'Язык: %s',
            $user_id,
            $from->getFirstName(),
            $from->getLastName(),
            $from->getUsername(),
            $from->getLanguageCode()
        );

        //Fetch user profile photo
        $limit = 10;
        $offset = null;
        $response = Request::getUserProfilePhotos(
            [
                'user_id' => $user_id,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        if ($response->isOk()) {
            /** @var UserProfilePhotos $user_profile_photos */
            $user_profile_photos = $response->getResult();

            if ($user_profile_photos->getTotalCount() > 0) {
                $photos = $user_profile_photos->getPhotos();

                /** @var PhotoSize $photo */
                $photo = $photos[0][2];
                $file_id = $photo->getFileId();

                $data['photo'] = $file_id;
                $data['caption'] = $caption;

                $result = Request::sendPhoto($data);

                //Download the photo after send message response to speedup response
                $response2 = Request::getFile(['file_id' => $file_id]);
                if ($response2->isOk()) {
                    /** @var File $photo_file */
                    $photo_file = $response2->getResult();
                    Request::downloadFile($photo_file);
                }

                return $result;
            }
        }

        //No Photo just send text
        $data['text'] = $caption;


        return Request::sendMessage($data);
    }
}
