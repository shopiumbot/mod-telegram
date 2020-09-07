<?php


/**
 * @var \shopium\mod\telegram\models\Mailing $model
 * @var \panix\engine\bootstrap\ActiveForm $form
 * @var \yii\base\DynamicModel $dy_model
 */

?>
<?= $form->field($dy_model, 'media')->fileInput() ?>
<?= $form->field($dy_model, 'thumb')->fileInput() ?>
<?= $form->field($dy_model, 'text')->textarea() ?>

