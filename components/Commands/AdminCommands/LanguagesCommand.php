<?php

namespace shopium\mod\telegram\components\Commands\AdminCommands;

use core\modules\shop\models\Product;
use core\modules\user\models\Payments;
use core\modules\user\models\User;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use panix\engine\CMS;
use shopium\mod\cart\models\Payment;
use shopium\mod\telegram\components\AdminCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;
use yii\base\Exception;
use yii\httpclient\Client;

/**
 * User "/languages" command
 */
class LanguagesCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'languages';

    /**
     * @var string
     */
    protected $description = 'Курс валют';

    /**
     * @var string
     */
    protected $usage = '/languages';

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * @var bool
     */
    protected $show_in_help = false;
    protected $enabled = true;
    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Conversation Object
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute()
    {

        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
        ];
        if ($text === $this->keyword_cancel) {
            return $this->telegram->executeCommand('cancel');
        }
        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }
        //   $notes['status'] = false;
        $result = Request::emptyResponse();

        $langs=[];
        foreach (Yii::$app->languageManager->getLanguages() as $lang) {
            $langs[$lang->code] = $lang->icon . ' ' . $lang->name;
        }
        switch ($state) {
            case 0:
                if ($text === '' || !in_array($text, array_values($langs), true)) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = Yii::t('telegram/default', 'CHOOSE_LANGUAGE') . ':';
                    //$data['reply_markup'] = Keyboard::remove(['selective' => true]);

                    $keyboards = [];
                    foreach ($langs as $k => $lang) {
                        $keyboards[] = new KeyboardButton(['text' => $lang]);
                    }
                    $keyboards = array_chunk($keyboards, 3);


                    /*$keyboards[] = [
                        new KeyboardButton('🇷🇺 Русский'),
                        new KeyboardButton('🇺🇦 Український'),
                        new KeyboardButton('🇬🇧 English') //🇺🇸
                    ];*/
                    $keyboards[] = [
                        new KeyboardButton($this->keyword_cancel),
                    ];

                    $buttons = (new Keyboard(['keyboard' => $keyboards]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);


                    if ($text !== '') {
                        $data['text'] = Yii::t('telegram/default', 'CHOOSE_LANGUAGE') . ':';
                    }
                    $data['reply_markup'] = $buttons;
                    $result = Request::sendMessage($data);
                    if ($result->isOk()) {
                        $db = DB::insertMessageRequest($result->getResult());
                    }
                    break;
                }

                $notes['language'] = array_search($text, $langs);
                $notes['language_name'] = $text;
                $text = '';

            // no break
            case 1:

                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();
                    $_user = \shopium\mod\telegram\models\User::findOne($user->getId());
                    if ($notes['language'] == $user->getLanguageCode()) {
                        $_user->language = NULL;
                    } else {
                        $_user->language = $notes['language'];
                    }

                    $_user->save(false);

                    $result = Request::emptyResponse();
                    $this->conversation->stop();
                    $this->telegram->executeCommand('start');
                    break;
                }
                break;
        }

        return $result;


    }
}
