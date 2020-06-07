<?php

namespace shopium\mod\telegram\controllers\admin;

use panix\engine\CMS;
use Yii;
use shopium\mod\telegram\models\Message;
use panix\engine\controllers\AdminController;
use shopium\mod\telegram\models\search\MessageSearch;
use shopium\mod\telegram\components\Api;

class MessageController extends AdminController
{

    public $icon = 'settings';

    public $layout = '@theme/views/layouts/dashboard_fluid';

    public function actionIndex()
    {

        $api = new Api(Yii::$app->user->token);
        $this->pageName = Yii::t('app/default', 'SETTINGS');
        $this->breadcrumbs = [
            [
                'label' => $this->module->info['label'],
                'url' => $this->module->info['url'],
            ],
            $this->pageName
        ];
        $searchModel = new MessageSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public function actionLoadChat(){
        $api = new Api(Yii::$app->user->token);
        $user_id = Yii::$app->request->get('user_id');
        $model = Message::find()->where(['chat_id'=>$user_id])->limit(50)->all();
        //print_r($id);die;
       // return $id;
        return $this->renderAjax('load-chat',['model'=>$model]);
    }

}
