<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use Longman\TelegramBot\Entities\ServerResponse;
use shopium\mod\telegram\components\SystemCommand;
use Longman\TelegramBot\Request;

/**
 * New chat member command
 */
class NewchatmembersCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'newchatmembers';

    /**
     * @var string
     */
    protected $description = 'New Chat Members';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
       /* $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
        $members = $message->getNewChatMembers();

        $text = 'Hi there!';

        if (!$message->botAddedInChat()) {
            $member_names = [];
            foreach ($members as $member) {
                $member_names[] = $member->tryMention();
            }
            $text = 'Hi ' . implode(', ', $member_names) . '!';
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
        ];

        return Request::sendMessage($data);*/

        $message = $this->getMessage();
        $members = $message->getNewChatMembers();

        if ($message->botAddedInChat()) {
            return $this->replyToChat('Hi there, you BOT!');
        }

        $member_names = [];
        foreach ($members as $member) {
            $member_names[] = $member->tryMention();
        }

        return $this->replyToChat('Hi ' . implode(', ', $member_names) . '!');
    }
}
