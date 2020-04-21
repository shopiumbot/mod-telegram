<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace shopium\mod\telegram\commands\UserCommands;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\Command;
use Yii;

/**
 * User "/help" command
 */
class HelpCommand extends Command
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'help';
    protected $description = 'Show bot commands help';
    protected $usage = '/help or /help <command>';
    protected $version = '1.0';



    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message     = $this->getMessage();
        $chat_id     = $message->getChat()->getId();
        $command_str = trim($message->getText(true));

        // Admin commands shouldn't be shown in group chats
        $safe_to_show = $message->getChat()->isPrivateChat();

        $data = [
            'chat_id'    => $chat_id,
            'parse_mode' => 'markdown',
        ];



        $keyboards[] = [
            new KeyboardButton(['text' => '🏠 Начало']),
            new KeyboardButton(['text' => '✉ Написать']),
           // new KeyboardButton(['text' => '☎ Позвонить']),

        ];
      //  $keyboards[] = [
         //   new KeyboardButton(['text' => '✉ Написать']),
          //  new KeyboardButton(['text' => '⚙ Настройки']),
       // ];

        $reply_markup = (new Keyboard([
            'keyboard' => $keyboards
        ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true)->setSelective(true);


        $data['reply_markup'] = $reply_markup;


        list($all_commands, $user_commands, $admin_commands) = $this->getUserAdminCommands();
        // If no command parameter is passed, show the list.
        if ($command_str === '' || preg_match('/^(\x{2753})/iu', $command_str, $match)) {
            $data['text'] = '*'.Yii::t('telegram/command','COMMAND_LIST').'*:' . PHP_EOL;
            foreach ($user_commands as $user_command) {
                $data['text'] .= '/' . $user_command->getName() . ' - ' . $user_command->getDescription() . PHP_EOL;
            }

            if ($safe_to_show && count($admin_commands) > 0) {
                $data['text'] .= PHP_EOL . '*'.Yii::t('telegram/command','COMMAND_LIST_ADMIN').'*:' . PHP_EOL;
                foreach ($admin_commands as $admin_command) {
                    $data['text'] .= '/' . $admin_command->getName() . ' - ' . $admin_command->getDescription() . PHP_EOL;
                }
            }

            $data['text'] .= PHP_EOL . 'Для полной справки используйте: /help <command>';
            return Request::sendMessage($data);
        }

        $command_str = str_replace('/', '', $command_str);
        if (isset($all_commands[$command_str]) && ($safe_to_show || !$all_commands[$command_str]->isAdminCommand())) {
            $command      = $all_commands[$command_str];
            $data['text'] = sprintf(
                'Command: %s (v%s)' . PHP_EOL .
                'Description: %s' . PHP_EOL .
                'Usage: %s',
                $command->getName(),
                $command->getVersion(),
                $command->getDescription(),
                $command->getUsage()
            );
           // $result = $data;
           return Request::sendMessage($data);
        }

        $data['text'] = 'Помощь не доступна: Команда /' . $command_str . ' не найдена';

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
