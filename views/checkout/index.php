<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use panix\engine\Html;
use panix\engine\CMS;


$api = Yii::$app->telegram->getApi();

Pjax::begin([
    'dataProvider' => $dataProvider
]);
echo GridView::widget([
    'tableOptions' => ['class' => 'table table-striped'],
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        [
            'attribute' => 'user_id',
            'format' => 'raw',
            'value' => function ($model) use ($api) {
                return $model->user->displayName();
            }
        ],
        [
            'attribute' => 'order_info',
            'format' => 'raw',
            'value' => function ($model) use ($api) {
                return $model->order_info;
            }
        ],
        [
            'attribute' => 'total_amount',
            'format' => 'raw',
            'value' => function ($model) use ($api) {
                return Yii::$app->currency->number_format($model->total_amount).' '.$model->currency;
            }
        ],
        [
            'attribute' => 'invoice_payload',
            'format' => 'raw',
            'value' => function ($model) use ($api) {
                return $model->invoice_payload;
            }
        ],
        [
            'attribute' => 'shipping_option_id',
            'format' => 'raw',
            'value' => function ($model) use ($api) {
                return $model->shipping_option_id;
            }
        ],
        [
            'attribute' => 'created_at',
            'format' => 'raw',
            'value' => function ($model) {
                return CMS::date(strtotime($model->created_at));
            }
        ],
    ]
]);
Pjax::end();