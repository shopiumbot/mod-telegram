<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use panix\engine\Html;
use panix\engine\CMS;

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
            'header' => 'Имя',
            'attribute' => 'username',
            'format' => 'raw',
            'value' => function ($model) use ($api) {

                if ($model->type === 'channel') {
                    $type = ' <span class="badge badge-danger">' . $model->type . '</span>';
                } elseif ($model->type === 'group') {
                    $type = ' <span class="badge badge-warning">' . $model->type . '</span>';
                } elseif ($model->type === 'supergroup') {
                    $type = ' <span class="badge badge-info">' . $model->type . '</span>';
                } else { //private
                    $type = ' <span class="badge badge-success">' . $model->type . '</span>';
                }
                $admin = (in_array($model->id, $api->getAdminList()) && !in_array($model->id, $api->defaultAdmins)) ? ' <span class="badge badge-warning">Администратор</span>' : '';

                $content = '';

                if ($model->username) {
                    $content .= Html::a('@' . $model->username, 'tg://@' . $model->username) . '' . $type . '<br/>';
                }
                if ($model->type == 'private') {
                    $content .= $model->first_name . ' ' . $model->last_name . $admin;
                }else{
                    $content .= $model->title . $admin;
                }


                return $content;
            }
        ],
        'type',

        [
            'attribute' => 'updated_at',
            'format' => 'raw',
            'value' => function ($model) {
                return CMS::date(strtotime($model->updated_at));
            }
        ],
    ]
]);
Pjax::end();