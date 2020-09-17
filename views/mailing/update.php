<?php

use yii\helpers\Html;
use panix\engine\bootstrap\ActiveForm;
use panix\ext\multipleinput\MultipleInput;
use panix\ext\multipleinput\MultipleInputColumn;

/**
 * @var \shopium\mod\telegram\models\Mailing $model
 * @var \panix\engine\bootstrap\ActiveForm $form
 * @var \yii\web\View $view
 * @var \yii\base\DynamicModel $dy_model
 */

?>
<div class="card">
    <div class="card-header">
        <h5><?= Html::encode($this->context->pageName) ?></h5>
    </div>
    <?php
    if ($model->isNewRecord && !$model->type) {
        $form = ActiveForm::begin(['method' => 'GET']);
        ?>

        <div class="card-body">
            <?= $form->field($model, 'type')->dropDownList($model::typeList()) ?>
        </div>
        <div class="card-footer text-center">
            <?= Html::submitButton(Yii::t('app/default', 'CREATE', 0), ['name' => false, 'class' => 'btn btn-success']); ?>
        </div>

        <?php
        ActiveForm::end();

    } else {
        $form = ActiveForm::begin();
        ?>

        <div class="card-body">
            <?= $this->render($view, ['model' => $model, 'form' => $form, 'dy_model' => $dy_model]); ?>
            <?= $form->field($dy_model, 'disable_notification')->checkbox(['checked'=>true]) ?>
            <?= $form->field($dy_model, 'send_to_groups')->checkbox() ?>
            <?= $form->field($dy_model, 'send_to_supergroups')->checkbox() ?>
            <?= $form->field($dy_model, 'send_to_channels')->checkbox() ?>
            <?= $form->field($dy_model, 'send_to_users')->checkbox() ?>
            <?= $form->field($dy_model, 'send_to_admins')->checkbox() ?>

            <div class="form-group row">
                <div class="col-sm-4 col-md-4 col-lg-3 col-xl-2"></div>
                <div class="col-sm-8 col-md-8 col-lg-9 col-xl-10">
                    <?php
                    echo MultipleInput::widget([
                        'model' => $dy_model,
                        'attribute' => 'buttons',
                        'max' => 7,
                        'min' => 0,
                        'allowEmptyList' => false,
                        'enableGuessTitle' => true,
                        'showGeneralError' => true,
                        'addButtonPosition' => \panix\ext\multipleinput\MultipleInput::POS_HEADER, // show add button in the header
                        'columns' => [
                            [
                                'name' => 'label',
                                'title' => $model::t('Название'),
                                'enableError' => true,
                                'type' => MultipleInputColumn::TYPE_TEXT_INPUT,

                                'options' => ['class' => 'text-center2'],
                                'headerOptions' => [
                                    'style' => 'width: 70px;',
                                ],

                            ],
                            [
                                'name' => 'callback',
                                'title' => $model::t('Callback'),
                                'enableError' => true,
                                'type' => MultipleInputColumn::TYPE_TEXT_INPUT,
                                'options' => ['class' => 'text-center2'],
                                'headerOptions' => [
                                    'style' => 'width: 70px;',
                                ],

                            ],
                        ]
                    ]);
                    ?>
                </div>
            </div>

        </div>
        <div class="card-footer text-center">
            <?= $model->submitButton(); ?>

            <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#docsCallbackModal">
                Документация Callback's
            </button>
        </div>

        <?php ActiveForm::end(); ?>
        <?php

        $callbacks = [
            [
                'callback' => 'getList&model=catalog&id=<code>'.Html::encode('<CATEGORY_ID>').'</code>',
                'text' => 'Каталог'
            ],
            [
                'callback' => 'getList&model=brands&id=<code>'.Html::encode('<BRAND_ID>').'</code>',
                'text' => 'Бренд'
            ],
            [
                'callback' => 'getList&model=new',
                'text' => 'Новые товары'
            ],
            [
                'callback' => 'checkOut',
                'text' => 'Оформление заказа'
            ],
            [
                'callback' => 'getCart',
                'text' => 'Корзина'
            ],
            [
                'callback' => 'getHistory',
                'text' => 'История заказов'
            ],
            [
                'callback' => 'getProduct&id=<code>'.Html::encode('<PRODUCT_ID>').'</code>',
                'text' => 'Товар'
            ],
            [
                'callback' => 'addCart&product_id=<code>'.Html::encode('<PRODUCT_ID>').'</code>',
                'text' => 'Добавление товара в корзину'
            ],
        ]


        ?>



        <!-- Modal -->
        <div class="modal fade" id="docsCallbackModal" tabindex="-1" role="dialog" aria-labelledby="docsCallbackModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document" style="max-width: 800px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="docsCallbackModalLabel">Callback's</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">Укажите в поле Callback из списка или URL</div>
                        <table class="table table-striped">
                            <tr>
                                <th>Callback</th>
                                <th>Описание</th>
                            </tr>
                            <?php foreach($callbacks as $callback){ ?>
                                <tr>
                                    <td><?= $callback['callback']; ?></td>
                                    <td><?= $callback['text']; ?></td>
                                </tr>
                            <?php } ?>

                        </table>
                    </div>
                </div>
            </div>
        </div>



        <?php
    }
    ?>
</div>



