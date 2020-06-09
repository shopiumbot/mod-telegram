<?php

namespace shopium\mod\telegram\commands\AdminCommands;

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
    protected $description = 'Отправить сообщение всем пользователям бота';

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



    /**
     * Execute command
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute()
    {
        $text = $this->getMessage()->getText(true);

        if ($text === '') {
            return $this->replyToChat(Yii::t('telegram/command', 'USAGE', $this->getUsage()));
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
            return $this->replyToChat('Пользователи или чаты не найдены.');
        }

        $total = 0;
        $failed = 0;

        $text = 'Сообщение отправлено:' . PHP_EOL;

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
                    $type = 'пользователь';
                } else {
                    $name = $chat->getTitle();
                    $type = 'чат';
                }
            } else {
                $status = '❌ ';
                ++$failed;
            }
            ++$total;

            $text .= $total . ') ' . $status . ' ' . $type . ' ' . $name . PHP_EOL;
        }
        $text .= 'Доставлено: ' . ($total - $failed) . '/' . $total . PHP_EOL;

        return $this->replyToChat($text);
    }
}
