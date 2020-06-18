<?php

namespace shopium\mod\telegram\components\Commands\UserCommands;


use core\modules\contacts\models\SettingsForm;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
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
        $config = Yii::$app->settings->get('contacts');
        $data['chat_id'] = $chat_id;
        $data['text'] = '';
        if (!isset($config->latitude) && !isset($config->longitude)) {
            if (!$config->latitude && !$config->longitude) {
                $data['text'] .= '*Контактная информация*' . PHP_EOL . PHP_EOL;
            }
        }
        $address = Yii::$app->getModule('contacts')->getAddress();
        $phones = Yii::$app->getModule('contacts')->getPhones();
        $emails = Yii::$app->getModule('contacts')->getEmails();


        if ($address) {
            foreach ($address as $addr) {
                if (!empty($addr)) {
                    if (isset($config->latitude) && isset($config->longitude)) {
                        $title = 'Контактная информация' . PHP_EOL . PHP_EOL;
                        $venue = Request::sendVenue([
                            'chat_id' => $chat_id,
                            'latitude' => $config->latitude,
                            'longitude' => $config->longitude,
                            'title' => $title,
                            'address' => '🌍 ' . $addr,
                            'reply_markup' => $this->homeKeyboards()
                        ]);

                    } else {
                        $data['text'] .= '🌍 ' . $addr . '' . PHP_EOL;
                    }
                }
            }
        }


        if ($phones) {
            foreach ($phones as $phone) {
                if(!empty($phone['number'])){
                    $name='';
                    if(!empty($phone['name'])){
                        $name .= '(' . $phone['name'] . ')';
                    }
                    $data['text'] .= '📞 *' . CMS::phone_format($phone['number']) . '* ' . $name . '' . PHP_EOL;
                }

            }
        }
        if ($emails) {
            foreach ($emails as $email) {
                $data['text'] .= '✉ *' . $email . '*' . PHP_EOL;
            }
        }
        $data['parse_mode'] = 'Markdown';

        $data['reply_markup'] = $this->homeKeyboards();
        $response = Request::sendMessage($data);
        if ($response->isOk()) {
            $db = DB::insertMessageRequest($response->getResult());
        }


        if (isset($config->schedule) && $config->enable_schedule) {
            $data2['chat_id'] = $chat_id;
            $data2['text'] = '🗒 *' . Yii::t('contacts/default', 'SCHEDULE') . '*' . PHP_EOL . PHP_EOL;

            foreach ($config->schedule as $key => $schedule) {

                $isStatus = '';
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
                    $data2['text'] .= 'с ' . $schedule['start_time'] . ' до ' . $schedule['end_time'] . $isStatus . PHP_EOL;
                } else {
                    $data2['text'] .= SettingsForm::t('DAY_OFF') . PHP_EOL;
                }


            }
            $data2['parse_mode'] = 'Markdown';
            $responseSchedule = Request::sendMessage($data2);
            if ($responseSchedule->isOk()) {
                $db = DB::insertMessageRequest($responseSchedule->getResult());
            }
        }


        return Request::emptyResponse();

    }

}
