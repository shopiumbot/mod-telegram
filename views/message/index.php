<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use Longman\TelegramBot\Request;
use panix\engine\Html;
use panix\engine\CMS;

?>

<div class="chat-application">
    <!-- ============================================================== -->
    <!-- Left Part  -->
    <!-- ============================================================== -->
    <div class="left-part bg-white fixed-left-part user-chat-box">
        <!-- Mobile toggle button -->
        <a class="ti-menu ti-close btn btn-success show-left-part d-block d-md-none" href="javascript:void(0)"></a>
        <!-- Mobile toggle button -->
        <div class="p-3">
            <h4>Chat Sidebar</h4>
        </div>
        <div class="scrollable position-relative" style="height:100%;">
            <div class="p-3 border-bottom">
                <h5 class="card-title">Search Contact</h5>
                <form>
                    <div class="searchbar">
                        <input class="form-control" type="text" placeholder="Search Contact">
                    </div>
                </form>
            </div>
            <ul class="mailbox list-style-none app-chat">
                <li>
                    <div class="message-center chat-scroll chat-users">


                        <?php

                        $users = \shopium\mod\telegram\models\User::find()->where(['is_bot' => 0])->all();
                        if ($users) {
                            foreach ($users as $user) {

                                //$member = Request::getChatMember(['chat_id'=>'812367093','user_id'=>'812367093']);


                                ?>
                                <a href="javascript:void(0)" class="chat-user message-item" id='chat_user_1'
                                   data-user-id='<?= $user->id; ?>'>
                                        <span class="user-img">

                                            <img src="<?= $user->getPhoto(); ?>" alt="<?= $user->first_name; ?>"
                                                 class="rounded-circle">
                                            <span class="profile-status online pull-right"></span>
                                        </span>
                                    <div class="mail-contnet">
                                        <h5 class="message-title"
                                            data-username="Pavan kumar"><?= $user->first_name; ?></h5>
                                        <span class="mail-desc"><?= $user->lastMessage->text; ?></span> <span
                                                class="time"><?= $user->lastMessage->date; ?></span>
                                    </div>
                                </a>
                            <?php }
                        } ?>

                        <!-- Message -->
                        <a href="javascript:void(0)" class="chat-user message-item" id='chat_user_8' data-user-id='8'>
                                        <span class="user-img">
                                            <img src="<?= $this->context->asset->baseUrl; ?>/images/3.jpg" alt="user"
                                                 class="rounded-circle">
                                            <span class="profile-status offline pull-right"></span>
                                        </span>
                            <div class="mail-contnet">
                                <h5 class="message-title" data-username="Varun Dhavan">Varun Dhavan</h5>
                                <span class="mail-desc">Just see the my admin!</span>
                                <span class="time">9:02 AM</span>
                            </div>
                        </a>
                        <!-- Message -->
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End Left Part  -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- Right Part  Mail Compose -->
    <!-- ============================================================== -->
    <div class="right-part chat-container">
        <div class="p-3 chat-box-inner-part">
            <div class="chat-not-selected">
                <div class="text-center">
                    <span class="display-5 text-info"><i class="icon-comments"></i></span>
                    <h5>Open chat from the list</h5>
                </div>
            </div>
            <div class="card chatting-box mb-0">
                <div class="card-body">
                    <div class="chat-meta-user pb-3 border-bottom">
                        <div class="current-chat-user-name">
                                        <span>
                                            <img src="<?= $this->context->asset->baseUrl; ?>/images/2.jpg"
                                                 alt="dynamic-image" class="rounded-circle" width="45">
                                            <span class="name font-medium ml-2"></span>
                                        </span>
                        </div>
                    </div>
                    <!-- <h4 class="card-title">Chat Messages</h4> -->
                    <div class="chat-box scrollable" style="height:calc(100vh - 300px);">
                        <!--User 1 -->
                        <ul class="chat-list chat" data-user-id="812367093">
                            <!--chat Row -->

                            <?php

                            /*$user = \shopium\mod\telegram\models\User::find()->where(['id' => 812367093])->one();
                            if ($users) {
                                foreach ($user->chats as $message) {
                                   // CMS::dump($message);die;
                                    $odd = ($message->user_id == $message->chat_id) ? 'odd' : '';
                                    $imageClass = ($message->user_id == $message->chat_id) ? 'float-right' : '';
                                    ?>
                                    <li class="<?= $odd; ?> chat-item">
                                        <div class="chat-img <?= $imageClass ?>">
                                            <img src="123" alt="user1">
                                        </div>
                                        <div class="chat-content">
                                            <h6 class="font-medium"><?= $message->user_id; ?></h6>
                                            <div class="box bg-light-info"><?= $message->text; ?></div>

                                            <?php if ($message->reply_markup) {
                                                $data = json_decode($message->reply_markup);
                                                if ($data->inline_keyboard) {
                                                    echo '<div>';


                                                    foreach ($data->inline_keyboard as $k => $keyboard) {

                                                        echo '<span class="btn btn-secondary">' . $keyboard[0]->text . '</span>';
                                                    }

                                                    echo '</div>';
                                                }
                                            }
                                            ?>
                                            <?php if ($message->callback) { ?>
                                                <div>
                                                    <div class="box bg-light-info"><?= $message->callback->data ?></div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="chat-time"><?= date('H:i',strtotime($message->date));?></div>
                                    </li>


                                <?php }
                            }*/ ?>
                        </ul>
                        <!--User 2 -->
                        <ul class="chat-list chat" data-user-id="2">
                            <!--chat Row -->
                            <li class="chat-item">
                                <div class="chat-img"><img src="<?= $this->context->asset->baseUrl; ?>/images/2.jpg"
                                                           alt="user"></div>
                                <div class="chat-content">
                                    <h6 class="font-medium">James Anderson</h6>
                                    <div class="box bg-light-info">Lorem Ipsum is simply dummy text of the printing
                                        &amp; type setting industry.
                                    </div>
                                </div>
                                <div class="chat-time">10:56 am</div>
                            </li>
                            <!--chat Row -->
                            <li class="chat-item">
                                <div class="chat-img"><img src="<?= $this->context->asset->baseUrl; ?>/images/2.jpg"
                                                           alt="user"></div>
                                <div class="chat-content">
                                    <h6 class="font-medium">Bianca Doe</h6>
                                    <div class="box bg-light-info">It’s Great opportunity to work.</div>
                                </div>
                                <div class="chat-time">10:57 am</div>
                            </li>
                            <!--chat Row -->
                            <li class="odd chat-item">
                                <div class="chat-content">
                                    <div class="box bg-light-inverse">I would love to join the team.</div>
                                    <br>
                                </div>
                            </li>
                            <!--chat Row -->
                            <li class="odd chat-item">
                                <div class="chat-content">
                                    <div class="box bg-light-inverse">Whats budget of the new project.</div>
                                    <br>
                                </div>
                                <div class="chat-time">10:59 am</div>
                            </li>
                            <!--chat Row -->
                            <li class="chat-item">
                                <div class="chat-img"><img src="<?= $this->context->asset->baseUrl; ?>/images/2.jpg" alt="user"></div>
                                <div class="chat-content">
                                    <h6 class="font-medium">Angelina Rhodes</h6>
                                    <div class="box bg-light-info">Well we have good budget for the project</div>
                                </div>
                                <div class="chat-time">11:00 am</div>
                            </li>
                            <!--chat Row -->
                        </ul>
                        <!--User 3 -->
                        <ul class="chat-list chat" data-user-id="3">
                            <!--chat Row -->
                            <li class="chat-item">
                                <div class="chat-img"><img src="<?= $this->context->asset->baseUrl; ?>/images/2.jpg" alt="user"></div>
                                <div class="chat-content">
                                    <h6 class="font-medium">James Anderson</h6>
                                    <div class="box bg-light-info">Lorem Ipsum is simply dummy text of the printing
                                        &amp; type setting industry.
                                    </div>
                                </div>
                                <div class="chat-time">10:56 am</div>
                            </li>
                            <!--chat Row -->
                            <li class="chat-item">
                                <div class="chat-img"><img src="<?= $this->context->asset->baseUrl; ?>/images/2.jpg" alt="user"></div>
                                <div class="chat-content">
                                    <h6 class="font-medium">Bianca Doe</h6>
                                    <div class="box bg-light-info">It’s Great opportunity to work.</div>
                                </div>
                                <div class="chat-time">10:57 am</div>
                            </li>
                            <!--chat Row -->
                            <li class="odd chat-item">
                                <div class="chat-content">
                                    <div class="box bg-light-inverse">I would love to join the team.</div>
                                    <br>
                                </div>
                            </li>
                            <!--chat Row -->
                            <li class="odd chat-item">
                                <div class="chat-content">
                                    <div class="box bg-light-inverse">Whats budget of the new project.</div>
                                    <br>
                                </div>
                                <div class="chat-time">10:59 am</div>
                            </li>
                            <!--chat Row -->
                            <li class="chat-item">
                                <div class="chat-img"><img src="<?= $this->context->asset->baseUrl; ?>/images/2.jpg" alt="user"></div>
                                <div class="chat-content">
                                    <h6 class="font-medium">Angelina Rhodes</h6>
                                    <div class="box bg-light-info">Well we have good budget for the project</div>
                                </div>
                                <div class="chat-time">11:00 am</div>
                            </li>
                            <!--chat Row -->
                        </ul>

                    </div>
                </div>
                <div class="card-body border-top border-bottom chat-send-message-footer">
                    <div class="row">
                        <div class="col-12">
                            <div class="input-field mt-0 mb-0">
                                <input id="textarea1" placeholder="Type and hit enter"
                                       class="message-type-box form-control border-0" type="text">
                            </div>
                            сообщение будет отправлено от имени Бота.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
