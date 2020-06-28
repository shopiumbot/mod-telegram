<?php

namespace shopium\mod\telegram\components;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;
use yii\base\Exception;

//defined('TB_BASE_PATH') || define('TB_BASE_PATH', __DIR__);
define('TB_BASE_COMMANDS_PATH', __DIR__ . '/Commands');

class Api extends \Longman\TelegramBot\Telegram
{
    protected $version = '0.3.8';

    public $defaultAdmins = [812367093];
    const NS_SYSTEM_COMMANDS = '\shopium\mod\telegram\components\Commands\SystemCommands';
    const NS_USER_COMMANDS = 'shopium\\mod\\telegram\\components\\Commands\\UserCommands';
    const NS_ADMIN_COMMANDS = 'shopium\\mod\\telegram\\components\\Commands\\AdminCommands';
    const NS_COMMANDS = 'shopium\\mod\\telegram\\components';

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

    /**
     * @inheritdoc
     */
    public function getCommandObject($command, $filepath = null)
    {
        //if (isset($this->commands_objects[$command])) {
        //    return $this->commands_objects[$command];
       // }
        $which = ['System'];
        $this->isAdmin() && $which[] = 'Admin';
        $which[] = 'User';

        foreach ($which as $auth) {

            if ($filepath) {
                $command_namespace = $this->getFileNamespace($filepath);
            } else {
                $command_namespace = __NAMESPACE__ . '\\Commands\\' . $auth . 'Commands';
            }
            $command_class = $command_namespace . '\\' . $this->ucfirstUnicode($command) . 'Command';

            if (class_exists($command_class)) {
                $command_obj = new $command_class($this, $this->update);

                switch ($auth) {
                    case 'System':
                        if ($command_obj instanceof SystemCommand) {
                            return $command_obj;
                        }
                        break;

                    case 'Admin':
                        if ($command_obj instanceof AdminCommand) {
                            return $command_obj;
                        }
                        break;

                    case 'User':
                        if ($command_obj instanceof UserCommand) {
                            return $command_obj;
                        }
                        break;
                }
            }
        }

        return null;
    }

    public function getCommandsList()
    {
        $commands = [];

        foreach ($this->commands_paths as $path) {
            try {
                //Get all "*Command.php" files
                $files = new \RegexIterator(
                    new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($path)
                    ),
                    '/^.+Command.php$/'
                );

                foreach ($files as $file) {
                    //Remove "Command.php" from filename
                    $command      = $this->sanitizeCommand(substr($file->getFilename(), 0, -11));
                    $command_name = mb_strtolower($command);

                    if (array_key_exists($command_name, $commands)) {
                        continue;
                    }

                    require_once $file->getPathname();

                    $command_obj = $this->getCommandObject($command, $file->getPathname());
                    if ($command_obj instanceof Command) {
                        $commands[$command_name] = $command_obj;
                    }
                }
            } catch (Exception $e) {
                throw new TelegramException('Error getting commands from path: ' . $path, $e);
            }
        }

        return $commands;
    }

    public function executeCommand($command)
    {

        $command = mb_strtolower($command);
        //if (isset($this->commands_objects[$command])) {
         //   $command_obj = $this->commands_objects[$command];
        //} else {
            $command_obj = $this->getCommandObject($command);
       // }

        if (!$command_obj || !$command_obj->isEnabled()) {
            //Failsafe in case the Generic command can't be found
            if ($command === static::GENERIC_COMMAND) {
                throw new TelegramException('Generic command missing!');
            }

            //Handle a generic command or non existing one
            $this->last_command_response = $this->executeCommand(static::GENERIC_COMMAND);
        } else {
            //execute() method is executed after preExecute()
            //This is to prevent executing a DB query without a valid connection
            $this->last_command_response = $command_obj->preExecute();
        }

        return $this->last_command_response;
    }


    public function enableAdmins(array $admin_ids = [])
    {

        $admin_ids = array_merge($this->defaultAdmins,Yii::$app->user->getBotAdmins());

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