<?php

namespace shopium\mod\telegram\components;

use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use Yii;

//defined('TB_BASE_PATH') || define('TB_BASE_PATH', __DIR__);
define('TB_BASE_COMMANDS_PATH', __DIR__ . '/commands');

class Api extends \Longman\TelegramBot\Telegram
{
    protected $version = '0.2.27';
    private $config = [];
    public $defaultAdmins = [];//812367093
   // public $user;

    public function __construct($api_key = '')
    {
       // $this->user = Yii::$app->user;
        if (empty($api_key))
            $api_key = Yii::$app->user->token;

        parent::__construct($api_key, 'shopiumbot');
        Yii::info('load api');
        $this->enableAdmins();

        $this->setDownloadPath(Yii::getAlias('@app/web/telegram/downloads'));
        $this->setUploadPath(Yii::getAlias('@app/web/telegram/uploads'));

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

        $admin_ids = array_merge(Yii::$app->user->getBotAdmins(), $this->defaultAdmins);

        foreach ($admin_ids as $admin_id) {
            $this->enableAdmin((int)$admin_id);
        }

        return $this;
    }

    public function getBotPhoto()
    {
        $profile = Request::getUserProfilePhotos(['user_id' => $this->bot_id]); //812367093 me
        if ($profile->isOk()) {
            if ($profile->getResult()->photos && isset($profile->getResult()->photos[0])) {
                $photo = $profile->getResult()->photos[0][2];
                $file = Request::getFile(['file_id' => $photo['file_id']]);
                if (!file_exists(Yii::getAlias('@app/web/telegram/downloads') . DIRECTORY_SEPARATOR . $file->getResult()->file_path)) {
                    $download = Request::downloadFile($file->getResult());

                } else {
                    return '/telegram/downloads/' . $file->getResult()->file_path;
                }
            }
        } else {
            return '/uploads/no-image.jpg';
        }
    }


}