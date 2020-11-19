<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use panix\engine\Html;
use panix\engine\CMS;

/*$categoriesList = [];
$root = \core\modules\shop\models\Category::findOne(1);
$model = \core\modules\shop\models\Category::find()->dataTree(1);
foreach ($model as $cate){
CMS::dump($cate);
}
die;*/




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
            'header' => Yii::t('telegram/User','PHOTO'),
            'format' => 'raw',
            'contentOptions' => ['class' => 'text-center', 'style' => 'width:50px'],
            'value' => function ($model) {
                $content = '';
                $content .= Html::img($model->getPhoto(), ['class' => 'rounded-circle', 'width' => 50]) . '<br/>';
                $content .= '<span class="badge badge-secondary">ID: ' . $model->id . '</span>';
                return $content;
            }
        ],
        [
            'header' => Yii::t('telegram/User','FIRST_NAME'),
            'attribute' => 'username',
            'format' => 'raw',
            'value' => function ($model) use ($api) {
                $isBot = ($model->is_bot) ? ' <span class="badge badge-danger">Бот</span>' : '';

                $admin = (in_array($model->id, $api->getAdminList()) && !in_array($model->id,$api->defaultAdmins)) ? ' <span class="badge badge-warning">Администратор</span>' : '';

                $content = '';

                if ($model->username) {
                    $content .= Html::a('@' . $model->username, 'tg://@' . $model->username) . '' . $isBot . '<br/>';
                }
                $content .= $model->first_name . ' ' . $model->last_name . $admin;

                return $content;
            }
        ],
        'language_code',

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