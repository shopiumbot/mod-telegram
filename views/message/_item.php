<?php
use panix\engine\emoji\Emoji;

?>
<li class="chat-item <?= $odd; ?>" data-key="<?= $key['id']; ?>">
    <div class="chat-img <?= $imageClass ?>">
        <img src="<?= $photo; ?>" alt="<?= $userName; ?>">
    </div>
    <div class="chat-content">
        <h6 class="font-medium"><?= $userName; ?></h6>
        <pre class="box"><?php echo Emoji::emoji_unified_to_html($data->text); ?></pre>
            <?php
            // $entity_decoder = new \shopium\mod\telegram\components\EntityDecoder($message->getMessageObject());
            //   $decoded_text   = $entity_decoder->decode();
            //echo \shopium\mod\telegram\components\Helper::parseMarkdown($message->text, $message->entities); ?>
            <?php //CMS::dump($message->getMessageObject()); ?>

        <?php

        ?>
        <?php if ($data->reply_markup) {
            $dataJson = json_decode($data->reply_markup);
            if ($dataJson->inline_keyboard) {
                echo '<div>';
                foreach ($dataJson->inline_keyboard as $k => $keyboard) {
                    echo '<span class="btn btn-secondary">' . Emoji::emoji_unified_to_html($keyboard[0]->text) . '</span>';
                }
                echo '</div>';
            }
        }
        ?>
        <?php if ($data->callback) {
            foreach ($data->callback as $callback) {
                ?>
                <div>
                    <div class="box bg-light-info"><?= $callback->data ?></div>
                </div>
            <?php }
        }
        ?>
    </div>
    <div class="chat-time"><?= date('H:i', strtotime($data->date)); ?></div>
</li>