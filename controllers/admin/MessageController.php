<?php

namespace shopium\mod\telegram\controllers\admin;

use Longman\TelegramBot\Exception\TelegramException;
use shopium\mod\telegram\components\Api;
use Yii;
use core\components\controllers\AdminController;
use shopium\mod\telegram\models\SettingsForm;
use yii\base\UserException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class MessageController extends AdminController
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
                'label'=>'Emoji',
                'url'=>'https://emojipedia.org/apple/',
                'options'=>['target'=>'_blank']
            ]
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


}
