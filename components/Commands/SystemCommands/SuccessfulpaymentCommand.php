<?php

declare(strict_types=1);

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use shopium\mod\telegram\components\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;

class SuccessfulpaymentCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'successfulpayment';
    /**
     * @var string
     */
    protected $usage = '/successfulpayment';

    /**
     * @var string
     */
    protected $description = 'Handle successful payment';

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute() : ServerResponse
    {
        $payment = $this->getMessage()->getSuccessfulPayment();

        Yii::info("Payment success #1");

        if($payment) {
            Yii::info("Payment success: {$payment->getProviderPaymentChargeId()} {$payment->getTotalAmount()} {$payment->getCurrency()}");
            return Request::emptyResponse();
        }

        return Request::emptyResponse();
    }


}