<?php

namespace shopium\mod\telegram\controllers\admin;


use Yii;
use core\components\controllers\AdminController;
use shopium\mod\telegram\models\search\UserSearch;

class UsersController extends AdminController
{

    public $icon = 'user';

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
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }


}
