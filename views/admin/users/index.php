<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use panix\engine\Html;

/*$limit    = 10;
$offset   = null;
$response =  Yii::$app->telegram->getUserProfilePhotos([
    'user_id' => 812367093,
    'limit'   => $limit,
    'offset'  => $offset,
]);*/


//\panix\engine\CMS::dump($response->result->photos);

//\panix\engine\CMS::dump(Yii::$app->telegram->getFile(['file_id'=>$response->result->photos[0][0]->file_id]));


Pjax::begin([
    'dataProvider' => $dataProvider
]);
echo GridView::widget([
    'tableOptions' => ['class' => 'table table-striped'],
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        'id',
        [
            'attribute' => 'username',
            'format'=>'raw',
            'value' => function ($model) {
                return Html::a('@'.$model->username, 'tg://@' . $model->username);
            }
        ],
        'last_name',
        [
            'attribute' => 'first_name',
            'format'=>'raw',
            'value' => function ($model) {
                return $model->first_name;
            }
        ],
        [
            'attribute' => 'username',
            'format'=>'raw',
            'value' => function ($model) {
                return Html::a('@'.$model->username, 'tg://@' . $model->username);
            }
        ]
    ]
]);
Pjax::end();