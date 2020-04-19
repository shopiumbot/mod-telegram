<?php

namespace shopium\mod\telegram\commands\UserCommands;


use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\UserCommand;
use Yii;
/**
 * User "/cell" command
 */
class CallCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'call';
    protected $description = 'call number';
    protected $usage = '/call <number>';
    protected $version = '1.0.1';
    public $enabled = false;
    public $private_only = false;
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text = trim($message->getText(true));

        if ($text === '') {
            $text = Yii::t('telegram/command', 'USAGE', $this->getUsage());
        }

        $data = [
            'chat_id' => $chat_id,
            'text' => $text . 'zzz',
        ];

        return Request::sendMessage($data);
    }
}
