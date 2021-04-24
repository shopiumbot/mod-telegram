<?php

namespace shopium\mod\telegram\components\Commands\AdminCommands;

use shopium\mod\telegram\components\AdminCommand;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;

/**
 * Admin "/sendtoall" command
 */
class SendtoallCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'sendtoall';

    /**
     * @var string
     */
    protected $usage = '/sendtoall <ваше сообщение>';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;


    public function getDescription():string
    {
        return Yii::t('telegram/default', 'COMMAND_SENDTOALL');
    }

    /**
     * Execute command
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $text = $this->getMessage()->getText(true);

        if ($text === '') {
            return $this->replyToChat(Yii::t('telegram/default', 'USAGE_COMMAND', $this->getUsage()));
        }

        /** @var ServerResponse[] $results */
        $results = Request::sendToActiveChats(
            'sendMessage',     //callback function to execute (see Request.php methods)
            ['text' => $text], //Param to evaluate the request
            [
                'groups' => true,
                'supergroups' => true,
                'channels' => false,
                'users' => true,
            ]
        );

        if (empty($results)) {
            return $this->replyToChat(Yii::t('telegram/default', 'USERS_OR_CHAT_NO_FOUND'));
        }

        $total = 0;
        $failed = 0;

        $text = Yii::t('telegram/default', 'SUCCESS_SEND') . ':' . PHP_EOL;

        foreach ($results as $result) {
            $name = '';
            $type = '';
            if ($result->isOk()) {
                $status = '✅ ';

                /** @var Message $message */
                $message = $result->getResult();
                $chat = $message->getChat();
                if ($chat->isPrivateChat()) {
                    $name = $chat->getFirstName();
                    $type = Yii::t('telegram/default', 'USER');
                } else {
                    $name = $chat->getTitle();
                    $type = Yii::t('telegram/default', 'CHAT');
                }
            } else {
                $status = '❌ ';
                ++$failed;
            }
            ++$total;

            $text .= $total . ') ' . $status . ' ' . $type . ' ' . $name . PHP_EOL;
        }
        $text .= Yii::t('telegram/default', 'DELIVERED') . ': ' . ($total - $failed) . '/' . $total . PHP_EOL;

        return $this->replyToChat($text);
    }
}
