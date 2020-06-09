<?php
use panix\engine\CMS;

/**
 * @var \shopium\mod\telegram\components\Api $api
 * @var \shopium\mod\telegram\models\User $user
 **/
$api = $this->context->api;
//print_r($api);die;
$botPhoto = $api->getBotPhoto();
$userPhoto = $user->getPhoto();
$userName = $user->username;
$botName = $api->getBotUsername();
?>

<ul id="chat-id-<?= Yii::$app->request->get('user_id');?>" class="chat-list chat active-chat" data-user-id="<?= Yii::$app->request->get('user_id');?>">
    <!--chat Row -->

    <?php
if($model){

        foreach ($model as $message) {
            // CMS::dump($message);die;
            $odd = ($message->user_id == $message->chat_id) ? 'odd' : '';
            $imageClass = ($message->user_id == $message->chat_id) ? 'float-right' : '';
            if($message->user_id == $message->chat_id){
                $photo = $userPhoto;
                $userName = $userName;
            }else{
                $userName = $botName;
                $photo = $botPhoto;
            }

            ?>
            <li class="<?= $odd; ?> chat-item">
                <div class="chat-img <?= $imageClass ?>">
                    <img src="<?= $photo; ?>" alt="<?= $userName; ?>">
                </div>
                <div class="chat-content">
                    <h6 class="font-medium"><?= $userName; ?></h6>
                    <div class="box bg-light-info"><?= $message->text; ?></div>
                    <?php

                    ?>
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
                    <?php if ($message->callback) {
                        foreach ($message->callback as $callback){
                        ?>
                        <div>
                            <div class="box bg-light-info"><?= $callback->data ?></div>
                        </div>
                    <?php }
                    }
                    ?>
                </div>
                <div class="chat-time"><?= date('H:i',strtotime($message->date));?></div>
            </li>


        <?php } }  ?>
</ul>