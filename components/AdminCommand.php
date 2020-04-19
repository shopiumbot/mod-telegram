<?php

namespace shopium\mod\telegram\components;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

abstract class AdminCommand extends \Longman\TelegramBot\Commands\AdminCommand
{
    /**
     * @var bool
     */
    protected $private_only = true;

    public function homeKeyboards(){
        $keyboards[] = [
            new KeyboardButton(['text' => '🏠 Начало']),
        ];

        $data = (new Keyboard([
            'keyboard' => $keyboards
        ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true)->setSelective(true);

        return $data;
    }
}
