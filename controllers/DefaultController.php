<?php

namespace shopium\mod\telegram\controllers;

use Longman\TelegramBot\Request;
use panix\engine\CMS;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use shopium\mod\telegram\components\Api;
use Longman\TelegramBot\Exception\TelegramException;

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
        //Todo: сделать чтобы работало все без авторизации

        $login = Yii::$app->user->loginById(Yii::$app->params['client_id']);
        $basePath = \Yii::$app->getModule('telegram')->basePath;
        $commands_paths = [
            realpath($basePath . '/components/Commands') . '/SystemCommands',
            realpath($basePath . '/components/Commands') . '/AdminCommands',
            realpath($basePath . '/components/Commands') . '/UserCommands',
        ];
        try {

            // Create Telegram API object
            $telegram = new Api();



            $telegram->enableExternalMySql($db->pdo, $db->tablePrefix . 'telegram__');
            $telegram->addCommandsPaths($commands_paths);

            // Handle telegram webhook request
            $telegram->handle();

        } catch (TelegramException $e) {

            // Silence is golden!
            // log telegram errors
            //Yii::error($e->getMessage());
            return $e->getMessage();
        }
        return null;
    }

}
