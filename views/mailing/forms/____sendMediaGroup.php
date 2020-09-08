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

<?= $form->field($dy_model, 'thumb')->fileInput() ?>
<?= $form->field($dy_model, 'text')->textarea() ?>
<div class="form-group row">
    <div class="col-sm-4 col-md-4 col-lg-3 col-xl-2"></div>
    <div class="col-sm-8 col-md-8 col-lg-9 col-xl-10">
        <?php
        echo MultipleInput::widget([
            'model' => $dy_model,
            'attribute' => 'media2',
            'max' => 7,
            'min' => 1,
            'allowEmptyList' => false,
            'enableGuessTitle' => true,
            //'addButtonPosition' => \panix\ext\multipleinput\MultipleInput::POS_HEADER, // show add button in the header
            'columns' => [
                [
                    'name' => 'media', // can be ommited in case of static column
                    'title' => $model::t('MEDIA'),
                    'enableError' => false,
                    'type' => MultipleInputColumn::TYPE_STATIC,
                    'value' => function ($data, $i) use ($model) {

                        return Html::fileInput('DynamicModel[media][]',null);
                    },
                    'options' => ['class' => 'text-center'],
                    'headerOptions' => [
                        'style' => 'width: 70px;',
                    ],

                ],
            ]
        ]);
        ?>
    </div>
</div>
