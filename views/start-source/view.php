<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use panix\engine\Html;
use panix\engine\CMS;

/**
 * @var \yii\web\View $this
 * @var \shopium\mod\telegram\components\Api $api
 */

Pjax::begin([
    'dataProvider' => $dataProvider
]);
echo GridView::widget([
    //'layoutPath' => '@user/views/layouts/_grid_layout',
    'tableOptions' => ['class' => 'table table-striped'],
    'dataProvider' => $dataProvider,
    //'filterModel' => $searchModel,
    'layoutOptions' => [
        'title' => $this->context->pageName,
    ],
    'columns' => [
        [
            'attribute' => 'user_id',
            'format' => 'raw',
            'value' => function ($model) {
                return $model->user->displayName();
            }
        ],
        [
            'attribute' => 'created_at',
            'format' => 'raw',
            'value' => function ($model) {
                return CMS::date($model->created_at);
            }
        ],
    ]
]);
Pjax::end();