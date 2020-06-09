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


    public function actionSet()
    {
        Yii::$app->response->format = Response::FORMAT_HTML;
        try {
            // Create Telegram API object
            $telegram = new Api;

            if (!empty(\Yii::$app->modules['telegram']->userCommandsPath)) {
                if (!$commandsPath = realpath(\Yii::getAlias(\Yii::$app->modules['telegram']->userCommandsPath))) {
                    $commandsPath = realpath(\Yii::getAlias('@app') . \Yii::$app->modules['telegram']->userCommandsPath);
                }

                if (!is_dir($commandsPath)) throw new UserException('dir ' . \Yii::$app->modules['telegram']->userCommandsPath . ' not found!');
            }

            // Set webhook

            $result = $telegram->setWebHook('https://'.Yii::$app->request->serverName.'/telegram/default/hook');
            if ($result->isOk()) {
                return $result->getDescription();
            }
        } catch (TelegramException $e) {
            return $e->getMessage();
        }
        return null;
    }

    /**
     * @return null|string
     * @throws ForbiddenHttpException
     */
    public function actionUnset()
    {

        Yii::$app->response->format = Response::FORMAT_HTML;
        if (\Yii::$app->user->isGuest) throw new ForbiddenHttpException();
        try {
            // Create Telegram API object
            $telegram = new Api;

            // Unset webhook
            $result = $telegram->deleteWebhook();

            if ($result->isOk()) {
                return $result->getDescription();
            }
        } catch (TelegramException $e) {
            return $e->getMessage();
        }
    }
}
