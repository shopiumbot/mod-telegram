<?php


/**
 * @var \shopium\mod\telegram\models\Mailing $model
 * @var \panix\engine\bootstrap\ActiveForm $form
 * @var \yii\base\DynamicModel $dy_model
 */

?>

<?= $form->field($dy_model, 'latitude') ?>
<?= $form->field($dy_model, 'longitude') ?>
<?= $form->field($dy_model, 'title') ?>
<?= $form->field($dy_model, 'address')->textarea() ?>

<?= \panix\ext\leaflet\LeafletWidget::widget([
    'height' => '500px',
    'lat' => 46.44136,
    'lng' => 30.70430,
    'markers' => [
        [
            'coords' => [46.44136, 30.70430],
            'draggable' => true
        ]
    ]
]) ?>