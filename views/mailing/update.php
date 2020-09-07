<?php

use yii\helpers\Html;
use panix\engine\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use core\modules\shop\models\Manufacturer;
use panix\engine\jui\DatetimePicker;

/**
 * @var \shopium\mod\telegram\models\Mailing $model
 * @var \panix\engine\bootstrap\ActiveForm $form
 * @var \yii\web\View $view
 * @var \yii\base\DynamicModel $dy_model
 */

?>
<div class="card">
    <div class="card-header">
        <h5><?= Html::encode($this->context->pageName) ?></h5>
    </div>
    <?php
    if ($model->isNewRecord && !$model->type) {
        $form = ActiveForm::begin(['method' => 'GET']);
        ?>

        <div class="card-body">
            <?= $form->field($model, 'type')->dropDownList($model::typeList()) ?>


        </div>
        <div class="card-footer text-center">
            <?= Html::submitButton(Yii::t('app/default', 'CREATE', 0), ['name' => false, 'class' => 'btn btn-success']); ?>
        </div>

        <?php
        ActiveForm::end();

    } else {
        $form = ActiveForm::begin();
        ?>

        <div class="card-body">
            <?= $this->render($view, ['model' => $model, 'form' => $form,'dy_model'=>$dy_model]); ?>
            <?= $form->field($dy_model, 'disable_notification')->checkbox() ?>
            <?= $form->field($dy_model, 'send_to_groups')->checkbox() ?>
            <?= $form->field($dy_model, 'send_to_supergroups')->checkbox() ?>
            <?= $form->field($dy_model, 'send_to_channels')->checkbox() ?>
            <?= $form->field($dy_model, 'send_to_users')->checkbox() ?>
        </div>
        <div class="card-footer text-center">
            <?= $model->submitButton(); ?>
        </div>

        <?php ActiveForm::end();
    }
    ?>
</div>