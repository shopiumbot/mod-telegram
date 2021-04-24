<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use Longman\TelegramBot\Entities\ServerResponse;
use shopium\mod\telegram\components\SystemCommand;
use Longman\TelegramBot\Request;


/**
 * Pinned message command
 *
 * Gets executed when a message gets pinned.
 */
class PinnedmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'pinnedmessage';

    /**
     * @var string
     */
    protected $description = 'Message was pinned';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $pinned_message = $message->getPinnedMessage();


        //remove pinned message
        /*if ($message && $pinned_message) {
            return Request::deleteMessage([
                'chat_id' => $message->getChat()->getId(),
                'message_id' => $message->getMessageId(),
            ]);
        }*/

        return Request::emptyResponse();
    }
}
