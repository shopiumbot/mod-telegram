
<?=
$form->field($model, 'bot_admins')
    ->widget(\panix\ext\taginput\TagInput::class, ['placeholder' => 'ID'])
    ->hint('Введите ID и нажмите Enter');
?>