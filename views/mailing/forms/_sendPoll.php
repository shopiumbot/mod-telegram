<?php
use panix\ext\multipleinput\MultipleInput;
use panix\ext\multipleinput\MultipleInputColumn;
use panix\engine\Html;

/**
 * @var \shopium\mod\telegram\models\Mailing $model
 * @var \panix\engine\bootstrap\ActiveForm $form
 * @var \yii\base\DynamicModel $dy_model
 */

?>
<?= $form->field($dy_model, 'poll_question') ?>
<?= $form->field($dy_model, 'poll_is_anonymous')->checkbox() ?>
<?= $form->field($dy_model, 'poll_type') ?>
<?= $form->field($dy_model, 'poll_allows_multiple_answers')->checkbox() ?>




<div class="form-group row">
    <div class="col-sm-4 col-md-4 col-lg-3 col-xl-2"></div>
    <div class="col-sm-8 col-md-8 col-lg-9 col-xl-10">
        <?php
        echo MultipleInput::widget([
            'model' => $dy_model,
            'attribute' => 'poll_options',
            'max' => 7,
            'min' => 2,
            'allowEmptyList' => false,
            'enableGuessTitle' => true,
            'showGeneralError' => true,
            'addButtonPosition' => \panix\ext\multipleinput\MultipleInput::POS_HEADER, // show add button in the header
            'columns' => [
                [
                    'name' => 'option',
                    'title' => $model::t('Пункт'),
                    'enableError' => true,
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