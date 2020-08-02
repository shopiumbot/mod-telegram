<?php

use panix\engine\Html;
use panix\engine\CMS;
use yii\widgets\ActiveForm;

/**
 * @var \yii\web\View $this
 */
\panix\engine\emoji\EmojiAsset::register($this);

$bundle = \panix\engine\emoji\EmojiPickerAsset::register($this);
$this->registerJs("
    $(function () {

        // Initializes and creates emoji set from sprite sheet
        window.emojiPicker = new EmojiPicker({
            emojiable_selector: '[data-emojiable=true]',
            assetsPath: '" . $bundle->baseUrl . "/images',
            popupButtonClasses: 'icon-smile'
        });
        // Finds all elements with `emojiable_selector` and converts them to rich emoji input fields
        // You may want to delay this step if you have dynamically created input fields that appear later in the loading process
        // It can be called as many times as necessary; previously converted input fields will not be converted again
        window.emojiPicker.discover();

    });
");

$this->registerCss('
.emoji-picker-icon{right:110px}
');

$form = ActiveForm::begin();
?>
<div class="card">
    <div class="card-header"></div>

    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="input-field mt-0 mb-0">
                    <div class="emoji-picker-container">
                        <?php echo Html::activeLabel($model, 'text'); ?>
                        <div class="input-group">

                            <?php echo Html::activeTextInput($model, 'text', ['id' => 'chat_message', 'data-emojiable' => 'true', 'class' => 'message-type-box form-control', 'rows' => 1, 'style' => 'width:100%;resize:none;']); ?>

                            <?php echo Html::error($model, 'text'); ?>
                            <div class="input-group-append">
                                <?= Html::submitButton(Yii::t('app/default', 'SEND'), ['class' => 'btn btn-success']); ?>
                            </div>
                        </div>
                    </div>


                    <div class="form-check">
                        <?php echo Html::activeCheckbox($model, 'send_to_users', ['class' => 'form-check-input']); ?>
                        <?php echo Html::error($model, 'send_to_users'); ?>
                    </div>
                    <div class="form-check">
                        <?php echo Html::activeCheckbox($model, 'send_to_groups', ['class' => 'form-check-input']); ?>
                        <?php echo Html::error($model, 'send_to_groups'); ?>
                    </div>
                    <div class="form-check">
                        <?php echo Html::activeCheckbox($model, 'send_to_supergroups', ['class' => 'form-check-input']); ?>
                        <?php echo Html::error($model, 'send_to_supergroups'); ?>
                    </div>
                    <div class="form-check">
                        <?php echo Html::activeCheckbox($model, 'send_to_channels', ['class' => 'form-check-input']); ?>
                        <?php echo Html::error($model, 'send_to_channels'); ?>
                    </div>


                </div>


            </div>
        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>
