<?php

use panix\engine\Html;
use panix\engine\CMS;
use yii\widgets\ActiveForm;
use panix\engine\emoji\EmojiAsset;
use panix\engine\widgets\Pjax;

/**
 * @var \yii\web\View $this
 * @var \shopium\mod\telegram\models\User $user
 */
EmojiAsset::register($this);

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

.emoji-picker-icon{
right:110px
}
');
$api = Yii::$app->telegram->getApi();
$telegram = Yii::$app->telegram;

$botPhoto = $telegram->getPhoto();
$userPhoto = $user->getPhoto();
$userName = $user->displayName();


$me = \Longman\TelegramBot\Request::getMe();
if ($me->isOk()) {
    $botName = $me->getResult()->username;
} else {
    $botName = $telegram->api->getBotUsername();
}


?>

<div class="chat-application">

    <div class="left-part bg-white fixed-left-part user-chat-box">
        <a class="icon-menu icon-close btn btn-success show-left-part d-block d-md-none" href="javascript:void(0)"></a>
        <div class="p-3">
            <h4>Чаты</h4>
        </div>
        <div class="scrollable position-relative" style="height:100%;">
            <div class="p-3 border-bottom">
                <h5 class="card-title">Поиск</h5>
                <form>
                    <div class="searchbar">
                        <input class="form-control" type="text" placeholder="Поиск">
                    </div>
                </form>
            </div>
            <ul class="mailbox list-style-none app-chat">
                <li>
                    <div class="message-center chat-scroll chat-users">

                        <?php

                        $users = \shopium\mod\telegram\models\User::find()->where(['is_bot' => 0])->orderBy(['updated_at' => SORT_DESC])->all();
                        if ($users) {
                            foreach ($users as $userItem) {
                                $activeClass = (Yii::$app->request->get('user_id') == $userItem->id) ? 'bg-light' : '';
                                //$member = Request::getChatMember(['chat_id'=>'812367093','user_id'=>'812367093']);
                                ?>
                                <a href="javascript:void(0)" class="chat-user message-item <?= $activeClass; ?>"
                                   id='chat_user_<?= $userItem->id; ?>'
                                   data-user-id='<?= $userItem->id; ?>'>
                                        <span class="user-img">

                                            <img src="<?= $userItem->getPhoto(); ?>"
                                                 alt="<?= $userItem->displayName(); ?>"
                                                 class="rounded-circle">
                                            <span class="profile-status online pull-right d-none"></span>
                                        </span>
                                    <div class="mail-content">
                                        <h5 class="message-title"
                                            data-username="<?= $userItem->displayName(); ?>"><?= $userItem->displayName(); ?></h5>
                                        <?php if ($userItem->lastMessage) { ?>
                                            <span class="mail-desc"><?= \panix\engine\emoji\Emoji::emoji_unified_to_html($userItem->lastMessage->text); ?></span>
                                            <span class="time"><?= CMS::date(strtotime($userItem->lastMessage->date)); ?></span>
                                        <?php } ?>
                                    </div>
                                </a>
                            <?php }
                        } ?>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <div class="right-part chat-container">
        <div class="p-3 chat-box-inner-part">
            <div class="chat-not-selected d-none">
                <div class="text-center">
                    <span class="display-5 text-info"><i class="icon-comments"></i></span>
                    <h5>Откройте чат из списка</h5>
                </div>
            </div>
            <div class="card chatting-box mb-0" style="display: block">
                <div class="card-body">

                    <div class="chat-meta-user pb-3 border-bottom">
                        <div class="current-chat-user-name">
                            <span>
                                <img src="<?= $userPhoto; ?>" alt="<?= $userName; ?>" class="rounded-circle" width="45">
                                <span class="name font-medium ml-2"><?= $userName; ?></span>
                            </span>
                        </div>
                    </div>
                    <!-- <h4 class="card-title">Chat Messages</h4> -->
                    <div class="chat-box scrollable" style="height:calc(100vh - 350px);">
                        <?php
                        //Pjax::begin();
                        ?>

                        <?php
                        echo \yii\widgets\ListView::widget([
                            'id' => 'user-messages',
                            'dataProvider' => $provider,
                            'itemView' => function ($model, $key, $index, $widget) use ($user, $botName, $botPhoto) {
                                $odd = ($model->user_id == $model->chat_id) ? 'odd' : '';
                                $imageClass = ($model->user_id == $model->chat_id) ? 'float-right' : '';
                                if ($model->user_id == $model->chat_id) {
                                    $photo = $user->getPhoto();
                                    $userName = $user->displayName();
                                } else {
                                    $userName = '@' . $botName;
                                    $photo = $botPhoto;
                                }
                                return $this->render('_item', [
                                    'key' => $key,
                                    'index' => $index,
                                    'data' => $model,
                                    'odd' => $odd,
                                    'imageClass' => $imageClass,
                                    'photo' => $photo,
                                    'userName' => $userName,
                                    'user' => $user,
                                ]);
                            },
                            'emptyTextOptions' => ['class' => 'col alert alert-info text-center'],
                            //'layout' => '{sorter}{summary}{items}{pager}',
                            'layout' => '{items}<div class="col-12">{pager}</div>',
                            'options' => [
                                'class' => 'list-view chat-list chat active-chat',
                                'tag' => 'ul',
                                'data-user-id' => $user->id
                            ],

                            'itemOptions' => [
                                'tag' => false,
                                //'class' => 'item',
                            ],

                            'pager' => [
                                //'options'=>['class'=>'pagination justify-content-center'],
                                'class' => \panix\wgt\scrollpager\ScrollPager::class,
                                'triggerOffset' => false,
                                'prevTemplate' => '<div class="ias-trigger ias-trigger-prev" style="text-align: center; cursor: pointer;"><div><div>{text}</div></div></div>',
                                'triggerTemplate' => '<div class="ias-trigger" style="text-align: center; cursor: pointer;"><div><div>{text}</div></div></div>',
                                'spinnerTemplate' => '<div class="ias-spinner" style="text-align: center;"><div><div>' . Yii::t('app/default', 'LOADING') . '</div></div></div>',
                                'noneLeftTemplate' => '<div class="ias-noneleft" style="text-align: center;"><div><div>{text}</div></div></div>',
                                //'noneLeftTemplate' => false,
                                //'spinnerSrc' => $this->context->assetUrl . '/images/ajax.gif'
                                //'paginationOptions' => ['class' => 'pagination d-flex justify-content-center2'],
                                // 'eventOnLoaded' => new \yii\web\JsExpression("function(items,url,xhr){

                                // }"),

                                // 'eventOnNext' => new \yii\web\JsExpression("function(pageIndex){

                                // }"),
                                'item' => '.chat-item',
                                'negativeMargin' => 250,
                                // 'pagination' => true,
                                'overflowContainer' => '.chat-box',
                                'triggerText' => Yii::t('shop/default', 'Показать еще')

                            ],
                        ]);
                        ?>

                        <?php
                        // Pjax::end();
                        ?>


                    </div>
                </div>
                <?php
                $form = ActiveForm::begin();
                echo Html::activeHiddenInput($sendForm, 'user_id');

                $js = <<<JS
$('form').on('beforeSubmit', function(){
 var data = $(this).serialize();
 $.ajax({
 url: $(this).attr('action'),
 type: 'POST',
 data: data,
 success: function(res){
 console.log(res);
 },
 error: function(jqXHR, textStatus, errorThrown){
 console.log(textStatus,jqXHR,errorThrown);
 }
 });
 return false;
});
JS;
                $this->registerJs($js);
                ?>


                <div class="card-body border-top border-bottom chat-send-message-footer">
                    <div class="row">
                        <div class="col-12">
                            <div class="input-field mt-0 mb-0">
                                <div class="emoji-picker-container">
                                    <?php echo Html::activeLabel($sendForm, 'text'); ?>
                                    <div class="input-group">

                                        <?php echo Html::activeTextInput($sendForm, 'text', ['id' => 'chat_message', 'data-emojiable' => 'true', 'class' => 'message-type-box form-control', 'rows' => 1, 'style' => 'width:100%;resize:none;']); ?>

                                        <?php echo Html::error($sendForm, 'text'); ?>
                                        <div class="input-group-append">
                                            <?= Html::submitButton(Yii::t('app/default', 'SEND'), ['class' => 'btn btn-success']); ?>
                                        </div>
                                    </div>
                                </div>

                            </div>


                            <small class="text-muted">сообщение будет отправлено от имени Бота.</small>

                        </div>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
