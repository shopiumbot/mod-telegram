<?php

namespace shopium\mod\telegram\components\Commands\AdminCommands;

use panix\engine\CMS;
use shopium\mod\telegram\components\AdminCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;

/**
 * User "/plan" command
 */
class PlanCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'plan';

    /**
     * @var string
     */
    protected $description = 'Информация о тарифе';

    /**
     * @var string
     */
    protected $usage = '/plan';

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * @var bool
     */
    protected $show_in_help = true;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        $userData = Yii::$app->user;
        $admins = $this->telegram->getAdminList();

        $text = '';
        $text .= 'Текущий тариф: *' . Yii::$app->params['plan'][$userData->planId]['name'] . '*' . PHP_EOL;
        $text .= 'Доступно товаров: *' . Yii::$app->params['plan'][$userData->planId]['product_limit'] . ' шт.*' . PHP_EOL;

        $text .= 'Работает до: *' . CMS::date($userData->expire) . '*' . PHP_EOL . PHP_EOL;

        //remove default admin from list
        foreach ($this->telegram->defaultAdmins as $k => $a) {
            unset($admins[$k]);
        }
        if ($admins) {
            $text .= 'Администраторы:' . PHP_EOL;
            foreach ($admins as $admin) {
                $text .= '/whois' . $admin . '' . PHP_EOL;
            }
        }
        $data['chat_id'] = $message->getFrom()->getId();
        $data['text'] = $text;
        $data['parse_mode'] = 'Markdown';

        return Request::sendMessage($data);
    }
}
