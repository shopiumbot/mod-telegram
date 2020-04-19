<?php

namespace shopium\mod\telegram\components;

use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;

//defined('TB_BASE_PATH') || define('TB_BASE_PATH', __DIR__);
define('TB_BASE_COMMANDS_PATH', __DIR__ . '/commands');

class Api extends \Longman\TelegramBot\Telegram
{
    protected $version = '1.0.0';
    private $config = [];

    public function __construct()
    {
        $this->config = Yii::$app->settings->get('telegram');
        //  echo TB_BASE_PATH.PHP_EOL;
        // echo TB_BASE_COMMANDS_PATH.PHP_EOL;
        $api_key = $this->config->api_token;
        $bot_username = $this->config->bot_name;
        parent::__construct($api_key, $bot_username);
        $this->enableAdmins();

        $this->setDownloadPath(Yii::getAlias('@app/web/downloads/telegram'));
        $this->setUploadPath(Yii::getAlias('@app/web/uploads/telegram'));

    }

    public function handle()
    {
        if (empty($this->bot_username)) {
            throw new TelegramException('Bot Username is not defined!');
        }

        $this->input = Request::getInput();

        if (empty($this->input)) {
            throw new TelegramException('Input is empty!');
        }

        $post = json_decode($this->input, true);
        if (empty($post)) {
            throw new TelegramException('Invalid JSON!');
        }

        if ($response = $this->processUpdate(new Update($post, $this->bot_username))) {
            return $response->isOk();
        }

        return false;
    }

    public function getCommandObject($command)
    {
        $which = ['System'];
        $this->isAdmin() && $which[] = 'Admin';
        $which[] = 'User';

        foreach ($which as $auth) {
            $command_namespace = 'shopium\\mod\\telegram\\commands\\' . $auth . 'Commands\\' . $this->ucfirstUnicode($command) . 'Command';

            if (class_exists($command_namespace)) {
                return new $command_namespace($this, $this->update);
            }
        }

        return null;
    }
    public function enableAdmins($admin_ids = [])
    {
        $list = [];
        if (isset($this->config->bot_admins) && $this->config->bot_admins)
            $list = explode(',', $this->config->bot_admins);

        $admin_ids = array_merge($admin_ids, $list);

        foreach ($admin_ids as $admin_id) {
            $this->enableAdmin((int)$admin_id);
        }

        return $this;
    }



}