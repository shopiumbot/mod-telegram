<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use panix\engine\Html;
use panix\engine\CMS;

/**
 * @var \yii\web\View $this
 * @var \shopium\mod\telegram\components\Api $api
 */
$api = Yii::$app->telegram->getApi();
?>
<div class="alert alert-info">
    <?= Yii::t('telegram/StartSource','INFO',$api->getBotUsername()); ?>
</div>
<?php

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
            'header'=>Yii::t('telegram/StartSource','LAST'),
            'attribute' => 'user_id',
            'format' => 'raw',
            'value' => function ($model) {

                $query = \shopium\mod\telegram\models\StartSource::find();
                $query->where(['source'=>$model->source]);
                $query->orderBy(['id'=>SORT_DESC]);
                $result = $query->one();


                return $result->user->displayName();
            }
        ],

        [
            'attribute' => 'source',
            'format' => 'raw',
            'value' => function ($model) use ($api) {


                return Html::a('https://t.me/' . $api->getBotUsername() . '?start=' . $model->source, 'https://t.me/' . $api->getBotUsername() . '?start=' . $model->source);
            }
        ],
        [
            'header' => Yii::t('telegram/StartSource','COUNT'),
            'format' => 'raw',
            'contentOptions' => ['class' => 'text-center'],
            'headerOptions' => ['class' => 'text-center'],
            'value' => function ($model) {
                return Html::a($model->usersCount,['view','source'=>$model->source]);
            }
        ],
    ]
]);
Pjax::end();