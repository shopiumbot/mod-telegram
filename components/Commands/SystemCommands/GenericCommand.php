<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use core\modules\user\models\Payments;
use Longman\TelegramBot\DB;
use shopium\mod\telegram\components\SystemCommand;
use Longman\TelegramBot\Request;
use Yii;

/**
 * Generic command
 *
 * Gets executed for generic commands, when no other appropriate one is found.
 */
class GenericCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'generic';

    /**
     * @var string
     */
    protected $description = 'Handles generic commands or is executed by default when a command is not found';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $preCheckoutQuery = $this->getPreCheckoutQuery();
        $shippingQuery = $this->getShippingQuery();
        if ($preCheckoutQuery) {
            $response = Request::answerPreCheckoutQuery([
                'pre_checkout_query_id' => $preCheckoutQuery->getId(),
                'ok' => true
            ]);
            if ($response->getOk()) {
                //set order to PAY
                $payment = new Payments();
                $payment->system = 'liqpay';
                $payment->name='Тариф';
                $payment->type = 'balance';
                $payment->money = 300.00;
                if($payment->save(false)){

                }
            }
            return $response;

        }

        // $test = false;
        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {

            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $chat_id = $message->getChat()->getId();
            $user_id = $callbackQuery->getFrom()->getId();

        } else {
            $message = $this->getMessage();


            // if ($message) {
            $test = true;
            $chat_id = $message->getChat()->getId();
            $user_id = $message->getFrom()->getId();
            // }
        }
        //You can use $command as param
        //if ($message) {
        $command = $message->getCommand();
        // }

        //If the user is an admin and the command is in the format "/whoisXYZ", call the /whois command
        if (stripos($command, 'whois') === 0 && $this->telegram->isAdmin($user_id)) {
            return $this->telegram->executeCommand('whois');
        } elseif (stripos($command, 'product') === 0) {
            return $this->telegram->executeCommand('product');
        }
        //if ($test) {
        $text = Yii::t('telegram/command', 'COMMAND_NOT_FOUND_1', $command) . PHP_EOL;
        $text .= Yii::t('telegram/command', 'COMMAND_NOT_FOUND_2');
        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
        ];

        $result = Request::sendMessage($data);
        if ($result->isOk()) {
            $db = DB::insertMessageRequest($result->getResult());
        }
        return $result;
        //  }

    }
}
