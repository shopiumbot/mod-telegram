<?php

/** @var $this \yii\web\View */

use yii\helpers\Html;

echo Html::button('<i class="icon-sendmail"></i> <span>' . Yii::t('telegram/default', 'Online support') . '</span>', ['class' => 'btn btn-primary', 'id' => 'tlgrm-init-btn']);

$options = \yii\helpers\Json::htmlEncode(\Yii::$app->getModule('telegram')->options);
$this->registerJs(<<<JS
var telegramOptions = $options;
JS
, \yii\web\View::POS_BEGIN);

