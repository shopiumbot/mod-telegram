<?php
use panix\engine\CMS;
?>

<ul id="chat-id-<?= Yii::$app->request->get('user_id');?>" class="chat-list chat active-chat" data-user-id="<?= Yii::$app->request->get('user_id');?>">
    <!--chat Row -->

    <?php
if($model){

        foreach ($model as $message) {
            // CMS::dump($message);die;
            $odd = ($message->user_id == $message->chat_id) ? 'odd' : '';
            $imageClass = ($message->user_id == $message->chat_id) ? 'float-right' : '';
            ?>
            <li class="<?= $odd; ?> chat-item">
                <div class="chat-img <?= $imageClass ?>">
                    <img src="<?= $message->getPhoto(); ?>" alt="user1">
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


        <?php } }  ?>
</ul>