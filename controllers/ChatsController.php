<?php

namespace shopium\mod\telegram\controllers;

use Yii;
use core\components\controllers\AdminController;
use shopium\mod\telegram\models\forms\SendMessageForm;
use shopium\mod\telegram\models\search\ChatSearch;

class ChatsController extends AdminController
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
        $searchModel = new ChatSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        $sendForm = new SendMessageForm();
        if($sendForm->load(Yii::$app->request->post())){
            if($sendForm->validate()){
                $sendForm->send();
                return $this->refresh();
            }

        }
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'sendForm'=>$sendForm
        ]);
    }

}
