<?php

use panix\engine\Html;
use panix\engine\bootstrap\ActiveForm;

?>
<?php
$form = ActiveForm::begin();
?>
    <div class="card">
        <div class="card-header">
            <h5><?= $this->context->pageName ?></h5>
        </div>
        <div class="card-body">

            <?php
            echo panix\engine\bootstrap\Tabs::widget([
                'items' => [
                    [
                        'label' => 'Общие',
                        'content' => $this->render('_main', ['form' => $form, 'model' => $model]),
                        'active' => true,
                        'options' => ['id' => 'main'],
                    ],
                ],
            ]);
            ?>

        </div>
        <div class="card-footer text-center">
            <?= Html::submitButton(Yii::t('app/default', 'SAVE'), ['class' => 'btn btn-success']) ?>
        </div>
    </div>
<?php ActiveForm::end(); ?>