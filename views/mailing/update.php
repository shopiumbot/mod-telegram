<?php

use yii\helpers\Html;
use panix\engine\bootstrap\ActiveForm;
use panix\ext\multipleinput\MultipleInput;
use panix\ext\multipleinput\MultipleInputColumn;

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

            <div class="form-group row">
                <div class="col-sm-4 col-md-4 col-lg-3 col-xl-2"></div>
                <div class="col-sm-8 col-md-8 col-lg-9 col-xl-10">
                    <?php
                    echo MultipleInput::widget([
                        'model' => $dy_model,
                        'attribute' => 'buttons',
                        'max' => 7,
                        'min' => 0,
                        'allowEmptyList' => false,
                        'enableGuessTitle' => true,
                        'showGeneralError' => true,
                        'addButtonPosition' => \panix\ext\multipleinput\MultipleInput::POS_HEADER, // show add button in the header
                        'columns' => [
                            [
                                'name' => 'label',
                                'title' => $model::t('Название'),
                                'enableError' => true,
                                'type' => MultipleInputColumn::TYPE_TEXT_INPUT,

                                'options' => ['class' => 'text-center2'],
                                'headerOptions' => [
                                    'style' => 'width: 70px;',
                                ],

                            ],
                            [
                                'name' => 'url',
                                'title' => $model::t('URL'),
                                'enableError' => true,
                                'type' => MultipleInputColumn::TYPE_TEXT_INPUT,

                                'options' => ['class' => 'text-center2'],
                                'headerOptions' => [
                                    'style' => 'width: 70px;',
                                ],

                            ],
                            [
                                'name' => 'callback',
                                'title' => $model::t('Callback'),
                                'enableError' => false,
                                'type' => MultipleInputColumn::TYPE_TEXT_INPUT,

                                'options' => ['class' => 'text-center2'],
                                'headerOptions' => [
                                    'style' => 'width: 70px;',
                                ],

                            ],
                        ]
                    ]);
                    ?>
                </div>
            </div>

        </div>
        <div class="card-footer text-center">
            <?= $model->submitButton(); ?>
        </div>
        <?php ActiveForm::end();
    }
    ?>
</div>

<table class="table table-striped">
    <tr>
        <th>sad</th>
    </tr>
    <tr>
        <td><code>getList&model=catalog&id=<?= Html::encode('<CATEGORY_ID>');?></code></td>
    </tr>
    <tr>
        <td><code>getList&model=brands&id=<?= Html::encode('<BRAND_ID>');?></code></td>
    </tr>
    <tr>
        <td><code>getList&model=new</code></td>
    </tr>
    <tr>
        <td><code>checkOut</code></td>
    </tr>
</table>

