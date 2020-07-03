<?php

namespace shopium\mod\telegram\controllers;

use Yii;
use core\components\controllers\AdminController;
use shopium\mod\telegram\models\search\CheckoutSearch;

class CheckoutController extends AdminController
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
        $searchModel = new CheckoutSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());


        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

}
