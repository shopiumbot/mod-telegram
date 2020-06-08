<?php

namespace shopium\mod\telegram\controllers\admin;

use panix\engine\controllers\AdminController;
use Longman\TelegramBot\Exception\TelegramException;
use shopium\mod\telegram\components\Api;
use Yii;
use shopium\mod\telegram\models\SettingsForm;
use yii\base\UserException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class DefaultController extends AdminController
{

    public $icon = 'settings';

    public function actionIndex()
    {
        $this->pageName = Yii::t('app/default', 'SETTINGS');
        $this->breadcrumbs = [
            [
                'label' => $this->module->info['label'],
                'url' => $this->module->info['url'],
            ],
            $this->pageName
        ];
        $this->buttons=[
            [
                'label'=>'Включить бота',
                'url'=>['/admin/telegram/default/set'],
                'options'=>['class'=>'btn btn-success']
            ],
            [
                'label'=>'Emoji',
                'url'=>'https://emojipedia.org/apple/',
                'options'=>['target'=>'_blank']
            ],
        ];
        $model = new SettingsForm();
        if ($model->load(Yii::$app->request->post())) {
            $model->save();
            return Yii::$app->getResponse()->redirect(['/admin/telegram']);
        }
        return $this->render('index', [
            'model' => $model
        ]);
    }

    public function actionSet(){
        Yii::$app->response->format = Response::FORMAT_HTML;
        try {
            // Create Telegram API object
            $telegram = new Api;

            if (!empty(\Yii::$app->modules['telegram']->userCommandsPath)){
                if(!$commandsPath = realpath(\Yii::getAlias(\Yii::$app->modules['telegram']->userCommandsPath))){
                    $commandsPath = realpath(\Yii::getAlias('@app') . \Yii::$app->modules['telegram']->userCommandsPath);
                }

                if(!is_dir($commandsPath)) throw new UserException('dir ' . \Yii::$app->modules['telegram']->userCommandsPath . ' not found!');
            }

            // Set webhook
            $result = $telegram->setWebHook(Yii::$app->modules['telegram']->hook_url);
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
    public function actionUnset(){
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
