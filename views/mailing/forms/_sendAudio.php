<?php
use panix\engine\CMS;

/**
 * @var \shopium\mod\telegram\models\Mailing $model
 * @var \panix\engine\bootstrap\ActiveForm $form
 * @var \yii\base\DynamicModel $dy_model
 */

?>
<?= $form->field($dy_model, 'media')->fileInput()->hint('max file size '.CMS::fileSize(1024*1024*50)) ?>
<?= $form->field($dy_model, 'thumb')->fileInput() ?>
<?= $form->field($dy_model, 'title') ?>
<?= $form->field($dy_model, 'duration') ?>
<?= $form->field($dy_model, 'performer') ?>
<?= $form->field($dy_model, 'text')->textarea() ?>

