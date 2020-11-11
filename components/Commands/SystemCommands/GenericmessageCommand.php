<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use core\modules\pages\models\Pages;
use Couchbase\RegexpSearchQuery;
use Longman\TelegramBot\Entities\Games\Game;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use panix\engine\CMS;
use Yii;
use shopium\mod\telegram\components\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Execution if MySQL is required but not available
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function executeNoDb()
    {
        // Try to execute any deprecated system commands.
        if (self::$execute_deprecated && $deprecated_system_command_response = $this->executeDeprecatedSystemCommand()) {
            return $deprecated_system_command_response;
        }

        return Request::emptyResponse();
    }

    /**
     * Execute command
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute()
    {
        $update = $this->getUpdate();
        // Try to continue any active conversation.
        if ($active_conversation_response = $this->executeActiveConversation()) {
            return $active_conversation_response;
        }


        // Try to execute any deprecated system commands.
        if (self::$execute_deprecated && $deprecated_system_command_response = $this->executeDeprecatedSystemCommand()) {
            return $deprecated_system_command_response;
        }


        $user_id = $this->getMessage()->getFrom()->getId();
        $chat_id = $this->getMessage()->getChat()->getId();

        $text = trim($this->getMessage()->getText());

        if ($text === static::KEYWORD_CANCEL) {
            return $this->telegram->executeCommand('cancel');
            //  return Request::emptyResponse();
        }

        $dice = $this->getMessage()->getDice();
        if ($dice) {
            $text_no_win = "К сожелению Вы не чего не выйграли!";
            $diceValue = $dice->getValue();
            if ($dice->getEmoji() == '🏀') {
                sleep(4);//time of animation game dice
                if (!in_array($diceValue, [1, 2, 3])) {
                    $text = "Вы выйграли *{$diceValue}%* скидки".PHP_EOL.PHP_EOL;
                    $text .= "Процент выйгрыша 40.00%";
                    $this->notify($text, 'success');
                } else {
                    $this->notify($text_no_win, 'info');
                }
            } elseif ($dice->getEmoji() == '🎯') {
                sleep(3);//time of animation game dice
                if ($diceValue != 1) {
                    $text = "Вы выйграли *{$diceValue}%* скидки".PHP_EOL.PHP_EOL;
                    $text .= "Процент выйгрыша 83.33%";
                    $this->notify($text, 'success');
                } else {
                    $this->notify($text_no_win, 'info');
                }

            } elseif ($dice->getEmoji() == '🎲') {
                sleep(4);//time of animation game dice
                $text = "Вы выйграли *{$diceValue}%* скидки";
                $this->notify($text, 'success');
            } elseif ($dice->getEmoji() == '🎰') {
                sleep(4);//time of animation game dice
                $text = "Вы выйграли *{$diceValue}*";
                $this->notify($text, 'success');

            }
        }

        /*$keyboards2[] = [
            new InlineKeyboardButton([
                'text' => '🎲 Выйграть скидку',
                'callback_data' => "query=productSpinnesad"
            ]),
        ];
        $keyboards2[] = [
            new InlineKeyboardButton([
                'text' => '🏀',
                'callback_data' => "query=deleteInCar"
            ]),
        ];
        $keyboards2[] = [
            new InlineKeyboardButton([
                'text' => '🎯',
                'callback_data' => "query=deleteInCar"
            ]),
        ];

        $data['chat_id'] = $chat_id;
        $data['text'] = 'Выигрывай скидку или промо-код';
        $data['parse_mode'] = 'Markdown';
        $data['reply_markup'] = new InlineKeyboard([
            'inline_keyboard' => $keyboards2
        ]);
        $send = Request::sendMessage($data);*/

        //$test['chat_id'] = '@shopiumbotchannel';
        // $test['chat_id'] = -1001271165607;
        // $test['text']='test';
        // $result = Request::sendMessage($test);


        $page = Pages::find()->published()->where(['name' => $text])->asArray()->one();
        if ($page) {
            $data['chat_id'] = $chat_id;
            $data['text'] = $page['text'];
            $data['parse_mode'] = 'Markdown';
            $data['reply_markup'] = $this->catalogKeyboards();
            $send = Request::sendMessage($data);
            if (!$send->isOk()) {
                return $this->notify($send->getDescription(), 'error');
            }
            return $send;
        }


        if ($this->settings->button_text_cart === $text) { //cart emoji //preg_match('/^(\x{1F6CD})/iu', $text, $match)
            return $this->telegram->executeCommand('cart');
        } elseif ($this->settings->button_text_catalog === $text) { //folder emoji preg_match('/^(\x{1F4C2})/iu', $text, $match)
            $this->telegram->setCommandConfig('catalog', ['id' => 1]);
            return $this->telegram->executeCommand('catalog');
        } elseif ($this->settings->button_text_start === $text || preg_match('/^(\x{1F3E0})/iu', $text, $match)) { //home emoji //preg_match('/^(\x{1F3E0})/iu', $text, $match)
            $this->telegram->executeCommand('start');
            return $this->telegram->executeCommand('cancel');
        } elseif (self::KEYWORD_ADMIN === $text) {
            if ($this->telegram->isAdmin($user_id)) {
                return $this->telegram->executeCommand('AdminPanel');
            }
        } elseif (preg_match('/^(\x{2753})/iu', $text, $match)) { //help emoji
            return $this->telegram->executeCommand('help');
        } elseif (preg_match('/^(\x{1F4E2})/iu', $text, $match)) { //news emoji
            return $this->telegram->executeCommand('news');
        } elseif (preg_match('/^(\x{260E}|\x{1F4DE})/iu', $text, $match)) { //phone emoji
            return $this->telegram->executeCommand('call');
        } elseif (preg_match('/^(\x{2709})/iu', $text, $match)) { //feedback emoji
            return $this->telegram->executeCommand('feedback');
        } elseif ($this->settings->button_text_history === $text) { //package emoji //preg_match('/^(\x{1F4E6})/iu', $text, $match)
            return $this->telegram->executeCommand('history');
        } elseif (preg_match('/^(\x{2699})/iu', $text, $match)) { //gear emoji
            return $this->telegram->executeCommand('settings');
        } elseif ($this->settings->button_text_search === $text) { //search emoji //preg_match('/^(\x{1F50E})/iu', $text, $match)
            return $this->telegram->executeCommand('search');
        }

        return Request::emptyResponse();
    }
}
