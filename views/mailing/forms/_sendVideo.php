<?php

use panix\engine\CMS;
/**
 * @var \shopium\mod\telegram\models\Mailing $model
 * @var \panix\engine\bootstrap\ActiveForm $form
 * @var \yii\base\DynamicModel $dy_model
 */

?>
<?= $form->field($dy_model, 'media')->fileInput()->hint('max file size '.CMS::fileSize(1024*1024*10)) ?>
<?= $form->field($dy_model, 'thumb')->fileInput() ?>
<?= $form->field($dy_model, 'text')->textarea() ?>

