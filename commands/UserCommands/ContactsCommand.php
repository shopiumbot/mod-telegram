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


use core\modules\contacts\models\SettingsForm;
use Longman\TelegramBot\Request;
use panix\engine\Html;
use shopium\mod\telegram\components\UserCommand;
use Yii;

/**
 * User "/contacts" command
 *
 * Display an inline keyboard with a few buttons.
 */
class ContactsCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'contacts';

    /**
     * @var string
     */
    protected $description = 'Контактная информация';

    /**
     * @var string
     */
    protected $usage = '/contacts';

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $data['chat_id'] = $chat_id;
        $data['text'] = '*Контактная информация*' . PHP_EOL . PHP_EOL;

        $address = Yii::$app->getModule('contacts')->getAddress();
        $phones = Yii::$app->getModule('contacts')->getPhones();
        $emails = Yii::$app->getModule('contacts')->getEmails();

        foreach ($address as $addr) {
            $data['text'] .= '🌍 Адрес: *' . $addr . '*' . PHP_EOL;
        }
        foreach ($phones as $phone) {
            $data['text'] .= '📞 Телефон: ' . $phone['number'] . ' ' . $phone['name'] . '' . PHP_EOL;
        }
        foreach ($emails as $email) {
            $data['text'] .= '✉ Почта: *' . $email . '*' . PHP_EOL;
        }
        $data['parse_mode'] = 'Markdown';

        $data['reply_markup'] = $this->homeKeyboards();
        $response= Request::sendMessage($data);

        $config = Yii::$app->settings->get('contacts');


        if (isset($config->schedule)) {
            $data2['chat_id'] = $chat_id;
            $data2['text'] = '🗒 *' . Yii::t('contacts/default', 'SCHEDULE') . '*' . PHP_EOL . PHP_EOL;

            foreach ($config->schedule as $key => $schedule) {

                $isStatus='';
                /*
                if (date('N') == $key + 1) {
                    if (Yii::$app->getModule('contacts')->getTodayOpen($key)) {
                        $isStatus = '🚫'.Yii::t('contacts/default', 'IS_CLOSE'). PHP_EOL;
                    } else {
                        $isStatus = Yii::t('contacts/default', 'IS_OPEN'). PHP_EOL;
                    }
                }*/


                $data2['text'] .= '🕗 *' . SettingsForm::dayList()[$key] . '* ';
                if (!empty($schedule['start_time']) || !empty($schedule['end_time'])) {
                    $data2['text'] .= 'с ' . $schedule['start_time'] . ' до ' . $schedule['end_time'].$isStatus. PHP_EOL;
                } else {
                    $data2['text'] .= SettingsForm::t('DAY_OFF'). PHP_EOL;
                }


             }
            $data2['parse_mode'] = 'Markdown';
            $responseSchedule = Request::sendMessage($data2);
        }


        return Request::emptyResponse();

    }

}
