<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use panix\engine\Html;
use panix\engine\CMS;
use shopium\mod\telegram\models\search\MailingSearch;

/*$limit    = 10;
$offset   = null;
$response =  Yii::$app->telegram->getUserProfilePhotos([
    'user_id' => 812367093,
    'limit'   => $limit,
    'offset'  => $offset,
]);*/


//\panix\engine\CMS::dump($response->result->photos);

//\panix\engine\CMS::dump(Yii::$app->telegram->getFile(['file_id'=>$response->result->photos[0][0]->file_id]));

/** @var \shopium\mod\telegram\components\Api $api */
$api = Yii::$app->telegram->getApi();

Pjax::begin([
    'dataProvider' => $dataProvider
]);
echo GridView::widget([
    //'layoutPath' => '@user/views/layouts/_grid_layout',
    'tableOptions' => ['class' => 'table table-striped'],
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        [
           // 'header' => 'Type',
            'attribute' => 'type',
            'format' => 'raw',
            'filter' => Html::dropDownList(Html::getInputName(new MailingSearch, 'type'), (isset(Yii::$app->request->get('MailingSearch')['type'])) ? Yii::$app->request->get('MailingSearch')['type'] : null, \shopium\mod\telegram\models\Mailing::typeList(),
                [
                    'class' => 'form-control',
                    'prompt' => html_entity_decode('&mdash; тип &mdash;')
                ]
            ),
            'value' => function ($model) {


                return str_replace('send','',$model->type);
            }
        ],
        [
           // 'header' => 'text',
            'attribute' => 'text',
            'format' => 'raw',
            'value' => function ($model) {


                return $model->text;
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