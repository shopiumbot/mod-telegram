<?php

namespace shopium\mod\telegram\components\Commands\AdminCommands;


use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Yii;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\AdminCommand;

/**
 * Admin "/adminpanel" command
 */
class AdminPanelCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'adminpanel';

    /**
     * @var string
     */
    protected $description = 'Админ панель';

    /**
     * @var string
     */
    protected $usage = '/adminpanel';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $show_in_help = false;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
        $command = $message->getCommand();
        $text = trim($message->getText(true));

        $keyboards[] = [
            new InlineKeyboardButton([
                'text' => '📦 '.Yii::t('shop/admin','CREATE_PRODUCT'),
                'callback_data' => "query=addProduct"
            ])
        ];
        /*$keyboards[] = [
            new InlineKeyboardButton([
                'text' => '✉ Рассылка',
                'callback_data' => "query=massmail"
            ])
        ];*/
        $keyboards[] = [
            new InlineKeyboardButton([
                'text' => '💸 Курс валют',
                'callback_data' => "query=exchangeRates"
            ])
        ];
        /*$keyboards[] = [
            new InlineKeyboardButton([
                'text' => 'Добавить админа',
                'callback_data' => "query=addAdmin"
            ])
        ];*/


        $data['chat_id'] = $chat_id;
        $data['text'] = Yii::t('admin/default','MODULE_NAME');



        $data['reply_markup'] = new InlineKeyboard([
            'inline_keyboard' => $keyboards
        ]);
        return Request::sendMessage($data);
    }
}
