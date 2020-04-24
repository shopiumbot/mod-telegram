<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;


Pjax::begin([
    'dataProvider'=>$dataProvider
]);
echo GridView::widget([
    'tableOptions' => ['class' => 'table table-striped'],
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        'user.username',

        [
            'attribute'=>'text',
            'format'=>'raw',
            'value'=>function($model){
                return $model->text;
            }
        ]
    ]
]);
Pjax::end();