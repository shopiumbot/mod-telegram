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

.emoji-picker-icon{
right:110px
}
');
$api = Yii::$app->telegram->getApi();


?>

<div class="chat-application">

    <div class="left-part bg-white fixed-left-part user-chat-box">
        <a class="icon-menu icon-close btn btn-success show-left-part d-block d-md-none" href="javascript:void(0)"></a>
        <div class="p-3">
            <h4>Chat Sidebar</h4>
        </div>
        <div class="scrollable position-relative" style="height:100%;">
            <div class="p-3 border-bottom">
                <h5 class="card-title"><?= Yii::t('default','SEARCH');?></h5>
                <form>
                    <div class="searchbar">
                        <input class="form-control" type="text" placeholder="<?= Yii::t('default','SEARCH');?>">
                    </div>
                </form>
            </div>
            <ul class="mailbox list-style-none app-chat">
                <li>
                    <div class="message-center chat-scroll chat-users">


                        <?php

                        $users = \shopium\mod\telegram\models\User::find()->where(['is_bot' => 0])->orderBy(['updated_at' => SORT_DESC])->all();
                        if ($users) {
                            foreach ($users as $user) {
                                $activeClass = (Yii::$app->request->get('user_id') == $user->id) ? 'bg-light' : '';
                                $userName = ($user->username) ? '@' . $user->username : $user->first_name . ' ' . $user->last_name;
                                //$member = Request::getChatMember(['chat_id'=>'812367093','user_id'=>'812367093']);
                                ?>
                                <a href="javascript:void(0)" class="chat-user message-item <?= $activeClass; ?>"
                                   id='chat_user_<?= $user->id; ?>'
                                   data-user-id='<?= $user->id; ?>'>
                                        <span class="user-img">

                                            <img src="<?= $user->getPhoto(); ?>" alt="<?= $user->first_name; ?>"
                                                 class="rounded-circle">
                                            <span class="profile-status online pull-right"></span>
                                        </span>
                                    <div class="mail-content">
                                        <h5 class="message-title"
                                            data-username="<?= $userName; ?>"><?= $userName; ?></h5>
                                        <?php if ($user->lastMessage) { ?>
                                            <span class="mail-desc"><?= \panix\engine\emoji\Emoji::emoji_unified_to_html($user->lastMessage->text); ?></span>
                                            <span
                                                    class="time"><?= CMS::date(strtotime($user->lastMessage->date)); ?></span>
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
            <div class="chat-not-selected">
                <div class="text-center">
                    <span class="display-5 text-info"><i class="icon-comments"></i></span>
                    <h5><?= Yii::t('telegram/default','OPEN_CHAT');?></h5>
                </div>
            </div>
            <div class="card chatting-box mb-0">
                <div class="card-body">

                    <div class="chat-meta-user pb-3 border-bottom">
                        <div class="current-chat-user-name">
                                        <span>
                                            <img src="<?= Yii::$app->request->baseUrl; ?>/uploads/no-image.jpg" alt="dynamic-image" class="rounded-circle"
                                                 width="45">
                                            <span class="name font-medium ml-2"></span>
                                        </span>
                        </div>
                    </div>
                    <!-- <h4 class="card-title">Chat Messages</h4> -->
                    <div class="chat-box scrollable" style="height:calc(100vh - 350px);">


                    </div>
                </div>
                <?php
                $form = ActiveForm::begin();
                echo Html::activeHiddenInput($sendForm, 'user_id');
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


                            <small class="text-muted"><?= Yii::t('telegram/default','MESSAGE_SEND_BY');?></small>

                        </div>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
