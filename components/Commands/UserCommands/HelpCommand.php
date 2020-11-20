<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\Command;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/help" command
 */
class HelpCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'help';
    protected $description = 'Показать команды бота';
    protected $usage = '/help or /help <command>';
    protected $version = '1.0';
    protected $show_in_help = false;


    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $command_str = trim($message->getText(true));

        // Admin commands shouldn't be shown in group chats
        $safe_to_show = $message->getChat()->isPrivateChat();

        $data = [
            'chat_id' => $chat_id,
            'parse_mode' => 'markdown',
        ];


        $start = \core\modules\menu\models\Menu::findOne(['callback' => 'start']);

        if ($start) {
            $keyboards[1][] = new KeyboardButton(['text' => $start->name]);
        }
        $keyboards[1][] = new KeyboardButton(['text' => Yii::t('telegram/default', 'WRITE')]);
        $keyboards[1][] = new KeyboardButton(['text' => Yii::t('telegram/default', 'HELP')]);


        $reply_markup = (new Keyboard([
            'keyboard' => $keyboards
        ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true)->setSelective(true);


        $data['reply_markup'] = $reply_markup;


        list($all_commands, $user_commands, $admin_commands) = $this->getUserAdminCommands();
        // If no command parameter is passed, show the list.
        if ($command_str === '' || preg_match('/^(\x{2753})/iu', $command_str, $match)) {
            $data['text'] = '*' . Yii::t('telegram/default', 'COMMAND_LIST') . '*:' . PHP_EOL;
            foreach ($user_commands as $user_command) {
                $data['text'] .= '/' . $user_command->getName() . ' - ' . $user_command->getDescription() . PHP_EOL;
            }

            if ($safe_to_show && count($admin_commands) > 0) {
                $data['text'] .= PHP_EOL . '*' . Yii::t('telegram/default', 'COMMAND_LIST_ADMIN') . '*:' . PHP_EOL;
                foreach ($admin_commands as $admin_command) {
                    $data['text'] .= '/' . $admin_command->getName() . ' - ' . $admin_command->getDescription() . PHP_EOL;
                }
            }

            $data['text'] .= PHP_EOL . Yii::t('telegram/default','FULL_HELP_USE').': /help <command>';
            return Request::sendMessage($data);
        }

        $command_str = str_replace('/', '', $command_str);
        if (isset($all_commands[$command_str]) && ($safe_to_show || !$all_commands[$command_str]->isAdminCommand())) {
            /** @var Command $command */
            $command = $all_commands[$command_str];

            $data['text'] = '*' . Yii::t('telegram/default', 'COMMAND') . ':* ' . $command->getName() . ' (v' . $command->getVersion() . ')' . PHP_EOL;
            $data['text'] .= '*' . Yii::t('telegram/default', 'DESCRIPTION') . ':* ' . $command->getDescription() . '' . PHP_EOL;
            $data['text'] .= '*' . Yii::t('telegram/default', 'USAGE') . ':* ' . $command->getUsage() . '' . PHP_EOL;

            $data['parse_mode'] = 'Markdown';
            return Request::sendMessage($data);
        }

        $data['text'] = Yii::t('telegram/default', 'COMMAND_NOT_FOUND', $command_str);

        return Request::sendMessage($data);
    }


    protected function getUserAdminCommands()
    {
        // Only get enabled Admin and User commands that are allowed to be shown.
        /** @var Command[] $commands */
        $commands = array_filter($this->telegram->getCommandsList(), function ($command) {
            /** @var Command $command */
            return !$command->isSystemCommand() && $command->showInHelp() && $command->isEnabled();
        });

        $user_commands = array_filter($commands, function ($command) {
            /** @var Command $command */
            return $command->isUserCommand();
        });

        $admin_commands = array_filter($commands, function ($command) {
            /** @var Command $command */
            return $command->isAdminCommand();
        });

        ksort($commands);
        ksort($user_commands);
        ksort($admin_commands);

        return [$commands, $user_commands, $admin_commands];
    }
}
