<?php

declare(strict_types=1);

namespace shopium\mod\telegram\commands\AdminCommands;

use shopium\mod\telegram\components\AdminCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 * Display user and chat information.
 */
class IdCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'id';

    /**
     * @var string
     */
    protected $description = 'Получить всю идентифицирующую информацию о текущем пользователе и чате';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = true;
    protected $show_in_help = false;
    /**
     * @return ServerResponse
     * @throws TelegramException
     */
    public function preExecute(): ServerResponse
    {
        $this->isPrivateOnly() && $this->removeNonPrivateMessage();

        // Make sure we only reply to messages.
        if (!$this->getMessage()) {
            return Request::emptyResponse();
        }

        return $this->execute();
    }

    /**
     * Execute command
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $user_info = '👤 *Пользователь Info*' . PHP_EOL . $this->getUserInfo();
        $chat_info = '🗣 *Чат Info*' . PHP_EOL . $this->getChatInfo();

        return $this->replyToUser($user_info . PHP_EOL . PHP_EOL . $chat_info, ['parse_mode' => 'markdown']);
    }

    /**
     * Get the information of the user.
     *
     * @return string
     */
    protected function getUserInfo(): string
    {
        $user = $this->getMessage()->getFrom();

        return implode(PHP_EOL, [
            "User Id: `{$user->getId()}`",
            'Имя: ' . (($first_name = $user->getFirstName()) ? "`{$first_name}`" : '_n/a_'),
            'Фамилия: ' . (($last_name = $user->getLastName()) ? "`{$last_name}`" : '_n/a_'),
            'Username: ' . (($username = $user->getUsername()) ? "`{$username}`" : '_n/a_'),
            'Язык: ' . (($language_code = $user->getLanguageCode()) ? "`{$language_code}`" : '_n/a_'),
        ]);
    }

    /**
     * Get the information of the chat.
     *
     * @return string
     */
    protected function getChatInfo(): string
    {
        $message = $this->getMessage();
        $chat    = $message->getForwardFromChat() ?? $message->getChat();

        if (!$chat || $chat->isPrivateChat()) {
            return '`Private chat`';
        }

        return implode(PHP_EOL, [
            "Тип: `{$chat->getType()}`",
            "Чат ID: `{$chat->getId()}`",
            'Название: ' . (($title = $chat->getTitle()) ? "`{$title}`" : '_n/a_'),
            'Имя: ' . (($first_name = $chat->getFirstName()) ? "`{$first_name}`" : '_n/a_'),
            'Фамилия: ' . (($last_name = $chat->getLastName()) ? "`{$last_name}`" : '_n/a_'),
            'Username: ' . (($username = $chat->getUsername()) ? "`{$username}`" : '_n/a_'),
        ]);
    }
}