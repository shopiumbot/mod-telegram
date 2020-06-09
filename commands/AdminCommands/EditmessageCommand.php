<?php

namespace shopium\mod\telegram\commands\AdminCommands;


use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\AdminCommand;

/**
 * User "/editmessage" command
 *
 * Command to edit a message via bot.
 */
class EditmessageCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'editmessage';

    /**
     * @var string
     */
    protected $description = 'Редактирование сообщения';

    /**
     * @var string
     */
    protected $usage = '/editmessage';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message          = $this->getMessage();
        $chat_id          = $message->getChat()->getId();
        $reply_to_message = $message->getReplyToMessage();
        $text             = $message->getText(true);

        if ($reply_to_message && $message_to_edit = $reply_to_message->getMessageId()) {
            $data_edit = [
                'chat_id'    => $chat_id,
                'message_id' => $message_to_edit,
                'text'       => $text ?: 'Edited message',
            ];

            // Try to edit selected message.
            $result = Request::editMessageText($data_edit);

            if ($result->isOk()) {
                // Delete this editing reply message.
                Request::deleteMessage([
                    'chat_id'    => $chat_id,
                    'message_id' => $message->getMessageId(),
                ]);
            }

            return $result;
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => sprintf("Ответьте на любое сообщение бота и используйте /%s <ваш текст> для его редактирования.", $this->name),
        ];

        return Request::sendMessage($data);
    }
}
