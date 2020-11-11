<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;

use shopium\mod\telegram\components\UserCommand;
use Longman\TelegramBot\Entities\File;
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
    protected $usage = '/me';
    protected $version = '1.0.1';
    protected $public = true;
    public $enabled = true;

    public function getDescription()
    {
        return Yii::t('telegram/default', 'COMMAND_ME');
    }

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


        $content = 'ID: ' . $user_id . (($this->telegram->isAdmin($user_id)) ? ' (Администратор)' : '') . PHP_EOL;
        $content .= 'Имя: ' . $from->getFirstName() . $from->getLastName() . PHP_EOL;
        $content .= 'Username: ' . $from->getUsername() . PHP_EOL;
        $content .= 'Язык: ' . $from->getLanguageCode();

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
                $data['caption'] = $content;
                //$data['parse_mode'] = 'Markdown'; //not work
                $result = Request::sendPhoto($data);
                if (!$result->isOk()) {
                    return $this->notify($result->getDescription(), 'error');
                }
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
        $data['text'] = $content;
        return Request::sendMessage($data);
    }

}
