<?php

namespace shopium\mod\telegram\controllers;

use panix\engine\data\ActiveDataProvider;
use shopium\mod\telegram\models\StartSource;
use Yii;
use core\components\controllers\AdminController;
use shopium\mod\telegram\models\search\StartSourceSearch;

class StartSourceController extends AdminController
{

    public $icon = 'settings';

    public function actionIndex()
    {

        $this->pageName = Yii::t('telegram/default', 'START_SOURCE');
        $this->breadcrumbs = [
            [
                'label' => $this->module->info['label'],
                'url' => $this->module->info['url'],
            ],
            $this->pageName
        ];
        $searchModel = new StartSourceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public function actionView($source)
    {

        $api = Yii::$app->telegram->getApi();
              //  return Html::a('https://t.me/' . $api->getBotUsername() . '?start=' . $model->source, 'https://t.me/' . $api->getBotUsername() . '?start=' . $model->source);

        $this->pageName = Yii::t('telegram/default', 'START_SOURCE').' "'.$source.'"';
        $this->breadcrumbs = [
            [
                'label' => $this->module->info['label'],
                'url' => $this->module->info['url'],
            ],
            $this->pageName
        ];

        $query = StartSource::find();
        $query->where(['source'=>$source]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('view', [
            'dataProvider' => $dataProvider,
           'api' => $api,
        ]);
    }

}
