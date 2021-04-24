<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use Longman\TelegramBot\Entities\ServerResponse;
use Yii;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\SystemCommand;

/**
 * User "/cancel" command
 *
 * This command cancels the currently active conversation and
 * returns a message to let the user know which conversation it was.
 * If no conversation is active, the returned message says so.
 */
class CancelCommand extends SystemCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'cancel';
    protected $description = 'Cancel the currently active conversation';
    protected $usage = '/cancel';
    protected $version = '1.0.0';
    protected $need_mysql = true;
    public $enabled = true;

    /**
     * {@inheritdoc}
     */
    public function execute(): ServerResponse
    {
        // $text = 'No active conversation!';
         $text = '';
        //Cancel current conversation if any
        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        if ($conversation_command = $conversation->getCommand()) {
            $conversation->cancel();

            if ($this->telegram->isAdmin($this->getMessage()->getFrom()->getId())) {
                $text .= ucfirst($conversation_command) . ': ';
            }
            $text = Yii::t('telegram/default','CANCELED');
        }
        if ($text) {
            return $this->hideKeyboard($text);
        } else {
            return Request::emptyResponse();
        }

    }

    /**
     * {@inheritdoc}
     */
    public function executeNoDb(): ServerResponse
    {
        return $this->hideKeyboard('Nothing to cancel.');
    }

    /**
     * Hide the keyboard and output a text
     *
     * @param string $text
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     */
    private function hideKeyboard($text)
    {

        $response = Request::sendMessage([
            'reply_markup' => $this->startKeyboards(),
            'chat_id' => $this->getMessage()->getChat()->getId(),
            'text' => $text,
        ]);
        if($response->isOk()){
            $db = DB::insertMessageRequest($response->getResult());
        }


        return $response;
    }
}
