<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use Longman\TelegramBot\Request;
use panix\engine\Html;
use panix\engine\CMS;
use yii\widgets\ActiveForm;

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
                            foreach ($users as $user) {
                                $userName = ($user->username) ? '@'.$user->username : $user->first_name . ' ' . $user->last_name;
                                //$member = Request::getChatMember(['chat_id'=>'812367093','user_id'=>'812367093']);
                                ?>
                                <a href="javascript:void(0)" class="chat-user message-item" id='chat_user_1'
                                   data-user-id='<?= $user->id; ?>'>
                                        <span class="user-img">

                                            <img src="<?= $user->getPhoto(); ?>" alt="<?= $user->first_name; ?>"
                                                 class="rounded-circle">
                                            <span class="profile-status online pull-right"></span>
                                        </span>
                                    <div class="mail-content">
                                        <h5 class="message-title"
                                            data-username="<?= $userName; ?>"><?= $userName; ?></h5>
                                        <?php if($user->lastMessage){ ?>
                                        <span class="mail-desc"><?= $user->lastMessage->text; ?></span> <span
                                                class="time"><?= $user->lastMessage->date; ?></span>
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
                    <h5>Откройте чат из списка</h5>
                </div>
            </div>
            <div class="card chatting-box mb-0">
                <div class="card-body">

                    <div class="chat-meta-user pb-3 border-bottom">
                        <div class="current-chat-user-name">
                                        <span>
                                            <img src="/uploads/no-image.jpg" alt="dynamic-image" class="rounded-circle"
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

                                <?php echo Html::activeLabel($sendForm, 'text'); ?>
                                <div class="input-group">
                                    <?php echo Html::activeTextInput($sendForm, 'text', ['class' => 'message-type-box form-control']); ?>
                                    <?php echo Html::error($sendForm, 'text'); ?>
                                    <div class="input-group-append">
                                        <?= Html::submitButton(Yii::t('app/default', 'SEND'), ['class' => 'btn btn-success']); ?>
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
