<?php
use panix\engine\CMS;
use panix\engine\Html;

/**
 * @var \shopium\mod\telegram\components\Api $api
 * @var \shopium\mod\telegram\models\User $user
 **/
$telegram = Yii::$app->telegram;
//print_r($api);die;
$botPhoto = $telegram->getPhoto();
$userPhoto = $user->getPhoto();
$userName = $user->username;


$me = \Longman\TelegramBot\Request::getMe();
if ($me->isOk()) {
    $botName = $me->getResult()->username;
} else {
    $botName = $telegram->api->getBotUsername();
}

?>

<ul id="chat-id-<?= Yii::$app->request->get('user_id'); ?>" class="chat-list chat active-chat"
    data-user-id="<?= Yii::$app->request->get('user_id'); ?>">

    <?php
    if ($model) {

        foreach ($model as $message) {
            /** @var \shopium\mod\telegram\models\Message $message */
            // CMS::dump($message);die;
            $odd = ($message->user_id == $message->chat_id) ? 'odd' : '';
            $imageClass = ($message->user_id == $message->chat_id) ? 'float-right' : '';
            if ($message->user_id == $message->chat_id) {
                $photo = $userPhoto;
                $userName = ($user->username) ? '@'.$user->username : $user->first_name . ' ' . $user->last_name;

            } else {
                $userName = '@'.$botName;
                $photo = $botPhoto;
            }

            ?>
            <li class="<?= $odd; ?> chat-item">
                <div class="chat-img <?= $imageClass ?>">
                    <img src="<?= $photo; ?>" alt="<?= $userName; ?>">
                </div>
                <div class="chat-content">
                    <h6 class="font-medium"><?= $userName; ?></h6>
                    <pre class="box bg-light-info">

                        <?php
                        echo $message->text;
                       // $entity_decoder = new \shopium\mod\telegram\components\EntityDecoder($message->getMessageObject());
                     //   $decoded_text   = $entity_decoder->decode();
                        //echo \shopium\mod\telegram\components\Helper::parseMarkdown($message->text, $message->entities); ?>
                        <?php //CMS::dump($message->getMessageObject()); ?>
                    </pre>
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
                        foreach ($message->callback as $callback) {
                            ?>
                            <div>
                                <div class="box bg-light-info"><?= $callback->data ?></div>
                            </div>
                        <?php }
                    }
                    ?>
                </div>
                <div class="chat-time"><?= date('H:i', strtotime($message->date)); ?></div>
            </li>


        <?php }
    } ?>
</ul>