<?php

namespace shopium\mod\telegram\components\Commands\AdminCommands;

use Yii;
use shopium\mod\telegram\components\AdminCommand;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class ChatsCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'chats';

    /**
     * @var string
     */
    protected $usage = '/chats, /chats * или /chats <search string>';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    public function getDescription()
    {
        return Yii::t('telegram/default', 'COMMAND_CHATS');
    }

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
        $text = trim($message->getText(true));

        $results = DB::selectChats([
            'groups' => true,
            'supergroups' => true,
            'channels' => true,
            'users' => true,
            'text' => ($text === '' || $text === '*') ? null : $text //Text to search in user/group name
        ]);

        $user_chats = 0;
        $group_chats = 0;
        $supergroup_chats = 0;
        $channel_chats = 0;

        if ($text === '') {
            $text_back = '';
        } elseif ($text === '*') {
            $text_back = 'Список всех чат-ботов:' . PHP_EOL;
        } else {
            $text_back = 'Результат поиска чата:' . PHP_EOL;
        }

        if (is_array($results)) {
            foreach ($results as $result) {
                //Initialize a chat object
                $result['id'] = $result['chat_id'];
                $chat = new Chat($result);

                $whois = $chat->getId();
                if ($this->telegram->getCommandObject('whois')) {
                    // We can't use '-' in command because part of it will become unclickable
                    $whois = '/whois' . str_replace('-', 'g', $chat->getId());
                }

                if ($chat->isPrivateChat()) {
                    if ($text !== '') {
                        $text_back .= '- P ' . $chat->tryMention() . ' [' . $whois . ']' . PHP_EOL;
                    }

                    ++$user_chats;
                } elseif ($chat->isSuperGroup()) {
                    if ($text !== '') {
                        $text_back .= '- S ' . $chat->getTitle() . ' [' . $whois . ']' . PHP_EOL;
                    }

                    ++$supergroup_chats;
                } elseif ($chat->isGroupChat()) {
                    if ($text !== '') {
                        $text_back .= '- G ' . $chat->getTitle() . ' [' . $whois . ']' . PHP_EOL;
                    }

                    ++$group_chats;
                } elseif ($chat->isChannel()) {
                    if ($text !== '') {
                        $text_back .= '- C ' . $chat->getTitle() . ' [' . $whois . ']' . PHP_EOL;
                    }

                    ++$channel_chats;
                }
            }
        }

        if (($user_chats + $group_chats + $supergroup_chats) === 0) {
            $text_back = 'Чаты не найдены...';
        } else {
            $text_back .= PHP_EOL . '(P) Частные чаты: ' . $user_chats;
            $text_back .= PHP_EOL . '(G) Группы: ' . $group_chats;
            $text_back .= PHP_EOL . '(S) Супер группы: ' . $supergroup_chats;
            $text_back .= PHP_EOL . '(C) Каналы: ' . $channel_chats;
            $text_back .= PHP_EOL . 'Всего: ' . ($user_chats + $group_chats + $supergroup_chats);

            if ($text === '') {
                $text_back .= PHP_EOL . PHP_EOL . 'Список всех чатов: /' . $this->name . ' *' . PHP_EOL . 'Search for chats: /' . $this->name . ' <search string>';
            }
        }

        $data = [
            'chat_id' => $chat_id,
            'text' => $text_back,
        ];

        return Request::sendMessage($data);
    }
}
