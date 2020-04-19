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

use shopium\mod\telegram\models\AuthorizedManagerChat;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Yii;

/**
 * User "/leavedialog" command
 */
class LeavedialogCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'leavedialog';
    protected $description = '';
    protected $usage = '/leavedialog';
    protected $version = '1.0.0';
    /**#@-*/

    public function __construct($telegram, $update = NULL)
    {
        $this->description = Yii::t('telegram/default', 'End the currently active conversation and switch to standby mode.');
        parent::__construct($telegram, $update);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $data = [
            'chat_id' => $chat_id,
        ];
        $authChat = AuthorizedManagerChat::findOne($chat_id);
        if (!$authChat){
            $data['text'] = Yii::t('telegram/default', 'You are not authorized!');
        }else {
            $currantChat = $authChat->client_chat_id;
            if ($currantChat) {
                $data['text'] = Yii::t('telegram/default', 'Completed conversation in chat ') . $currantChat;
                $authChat->client_chat_id = null;
                $authChat->save();
            } else {
                $data['text'] = Yii::t('telegram/default', 'You have no active conversations.');
            }
        }
        return Request::sendMessage($data);

    }
}
