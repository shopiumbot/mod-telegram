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
<?= $form->field($dy_model, 'media[]')->fileInput(['multiple'=>true])->hint(Yii::t('telegram/Mailing','HINT_FILE')) ?>
<?= $form->field($dy_model, 'text')->textarea() ?>
