<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use core\modules\user\models\Payments;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\ServerResponse;
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
     * @return ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {



        $message = $this->getMessage();

        // Handle new chat members
        if ($message->getNewChatMembers()) {
            //return $this->getTelegram()->executeCommand('newchatmembers');
        }

        // Handle left chat members
        if ($message->getLeftChatMember()) {
            //return $this->getTelegram()->executeCommand('leftchatmember');
        }

        // The chat photo was changed
        if ($new_chat_photo = $message->getNewChatPhoto()) {
            // Whatever...
        }

        // The chat title was changed
        if ($new_chat_title = $message->getNewChatTitle()) {
            // Whatever...
        }

        // A message has been pinned
        if ($pinned_message = $message->getPinnedMessage()) {
            // Whatever...
        }





        $preCheckoutQuery = $this->getPreCheckoutQuery();
        $shippingQuery = $this->getShippingQuery();
        $callbackQuery = $this->getCallbackQuery();
        if ($preCheckoutQuery) {
            $response = Request::answerPreCheckoutQuery([
                'pre_checkout_query_id' => $preCheckoutQuery->getId(),
                'ok' => true,
                //  'error_message'=>'error'
            ]);

            if ($response->getOk()) {
                $this->notify($response->getDescription(), 'error');
                //set order to PAY
                $payment = new Payments();
                $payment->system = 'liqpay';
                $payment->name = 'Тариф';
                $payment->type = 'balance';
                $payment->money = 300.00;
                if ($payment->save(false)) {

                }
                return $response;
            }


        }
        if ($shippingQuery) {
            $response = Request::answerShippingQuery([
                'shipping_query_id' => $shippingQuery->getId(),
                'ok' => true
            ]);

        }

        $update = $this->getUpdate();
        if ($update->getCallbackQuery()) {

            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $chat_id = $message->getChat()->getId();
            $user_id = $callbackQuery->getFrom()->getId();

        } else {
            $message = $this->getMessage();
            if ($message) {
                //  $chat = $message->getChat();
                //  $from = $message->getFrom();

                $chat_id = $message->getChat()->getId();
                $user_id = $message->getFrom()->getId();
                $command = $message->getCommand();


                //If the user is an admin and the command is in the format "/whoisXYZ", call the /whois command
                if (stripos($command, 'whois') === 0 && $this->telegram->isAdmin($user_id)) {
                    return $this->telegram->executeCommand('whois');
                } elseif (stripos($command, 'product') === 0) {
                    return $this->telegram->executeCommand('product');
                }


                $text = Yii::t('telegram/default', 'COMMAND_NOT_FOUND_1', $command) . PHP_EOL;
                $text .= Yii::t('telegram/default', 'COMMAND_NOT_FOUND_2');
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $text,
                ];

                $result = Request::sendMessage($data);
                if ($result->isOk()) {
                    $db = DB::insertMessageRequest($result->getResult());
                }
                return $result;
            }
        }
        return Request::emptyResponse();

    }
}
