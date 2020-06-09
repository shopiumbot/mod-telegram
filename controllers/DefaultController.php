<?php


namespace shopium\mod\telegram\controllers;

use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\telegram\components\Api;
use Yii;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use yii\base\UserException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;


/**
 * Default controller for the `telegram` module
 */
class DefaultController extends Controller
{


    public function beforeAction($action)
    {
        if ($action->id == 'hook') {
            $this->enableCsrfValidation = false;

        }
        return parent::beforeAction($action);
    }

    public function actionHook()
    {

        Yii::$app->response->format = Response::FORMAT_JSON;
        $db = Yii::$app->db;
        $mysql_credentials = [
            'host' => Yii::$app->getModule('telegram')->getDsnAttribute('host'),
            'user' => $db->username,
            'password' => $db->password,
            'database' => Yii::$app->getModule('telegram')->getDsnAttribute('dbname'),
        ];

        try {

            // Create Telegram API object
            $telegram = new Api();
            $basePath = \Yii::$app->getModule('telegram')->basePath;
            $commands_paths = [
                realpath($basePath . '/commands') . '/SystemCommands',
                realpath($basePath . '/commands') . '/AdminCommands',
                realpath($basePath . '/commands') . '/UserCommands',
            ];

            $telegram->enableMySql($mysql_credentials, $db->tablePrefix . 'telegram__');
            $telegram->addCommandsPaths($commands_paths);

            // Handle telegram webhook request
            $telegram->handle();

        } catch (TelegramException $e) {

            // Silence is golden!
            // log telegram errors
            Yii::error($e->getMessage());
            return $e->getMessage();
        }
        return null;
    }

}
