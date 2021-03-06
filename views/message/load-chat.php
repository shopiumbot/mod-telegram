<?php
use panix\engine\emoji\Emoji;
use panix\engine\emoji\EmojiAsset;
/**
 * @var \shopium\mod\telegram\components\Api $api
 * @var \shopium\mod\telegram\models\User $user
 * @var \yii\web\View $this
 **/
$telegram = Yii::$app->telegram;
$botPhoto = $telegram->getPhoto();



$me = \Longman\TelegramBot\Request::getMe();
if ($me->isOk()) {
    $botName = $me->getResult()->username;
} else {
    $botName = $telegram->api->getBotUsername();
}
EmojiAsset::register($this);

?>

<ul id="chat-id-<?= $user->id; ?>" class="chat-list chat active-chat"
    data-user-id="<?= $user->id; ?>">

    <?php
    if ($model) {

        foreach ($model as $message) {
            /** @var \shopium\mod\telegram\models\Message $message */
            $odd = ($message->user_id == $message->chat_id) ? 'odd' : '';
            $imageClass = ($message->user_id == $message->chat_id) ? 'float-right' : '';
            if ($message->user_id == $message->chat_id) {
                $photo = $user->getPhoto();
                $userName = $user->displayName();
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
                    <pre class="box"><?php echo Emoji::emoji_unified_to_html($message->text); ?>
                        <?php
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
                                echo '<span class="btn btn-secondary">' . Emoji::emoji_unified_to_html($keyboard[0]->text) . '</span>';
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