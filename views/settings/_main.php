
<?= $form->field($model, 'empty_cart_text')->textarea() ?>
<?php  echo $form->field($model, 'empty_history_text')->textarea() ?>

<?=
$form->field($model, 'bot_admins')
    ->widget(\panix\ext\taginput\TagInput::class, ['placeholder' => 'ID'])
    ->hint('Введите ID и нажмите Enter');
?>