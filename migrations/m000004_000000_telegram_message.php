<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m000004_000000_telegram_message extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable('{{%telegram__message}}', [
            'chat_id' => $this->bigInteger()->comment('Unique chat identifier'),
            'id' => $this->bigInteger()->unsigned()->comment('Unique message identifier'),
            'user_id' => $this->bigInteger()->null()->comment('Unique user identifier'),
            'date' => $this->timestamp()->null()->defaultValue(NULL)->comment('Date the message was sent in timestamp format'),
            'forward_from' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Unique user identifier, sender of the original message'),
            'forward_from_chat' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Unique chat identifier, chat the original message belongs to'),
            'forward_from_message_id' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Unique chat identifier of the original message in the channel'),
            'forward_signature' => $this->text()->null()->defaultValue(NULL)->comment('For messages forwarded from channels, signature of the post author if present'),
            'forward_sender_name' => $this->text()->null()->defaultValue(NULL)->comment('Sender\'s name for messages forwarded from users who disallow adding a link to their account in forwarded messages'),
            'forward_date' => $this->timestamp()->null()->defaultValue(NULL)->comment('date the original message was sent in timestamp format'),
            'reply_to_chat' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Unique chat identifier'),
            'reply_to_message' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('Message that this message is reply to'),
            'edit_date' => $this->bigInteger()->unsigned()->defaultValue(NULL)->comment('Date the message was last edited in Unix time'),
            'media_group_id' => $this->text()->comment('The unique identifier of a media message group this message belongs to'),
            'author_signature' => $this->text()->comment('Signature of the post author for messages in channels'),
            'text' => $this->text()->comment('For text messages, the actual UTF-8 text of the message max message length 4096 char utf8mb4'),
            'entities' => $this->text()->comment('For text messages, special entities like usernames, URLs, bot commands, etc. that appear in the text'),
            'caption_entities' => $this->text()->comment('For messages with a caption, special entities like usernames, URLs, bot commands, etc. that appear in the caption'),
            'audio' => $this->text()->comment('Audio object. Message is an audio file, information about the file'),
            'document' => $this->text()->comment('Document object. Message is a general file, information about the file'),
            'animation' => $this->text()->comment('Message is an animation, information about the animation'),
            'game' => $this->text()->comment('Game object. Message is a game, information about the game'),
            'photo' => $this->text()->comment('Array of PhotoSize objects. Message is a photo, available sizes of the photo'),
            'sticker' => $this->text()->comment('Sticker object. Message is a sticker, information about the sticker'),
            'video' => $this->text()->comment('Video object. Message is a video, information about the video'),
            'voice' => $this->text()->comment('Voice Object. Message is a Voice, information about the Voice'),
            'video_note' => $this->text()->comment('VoiceNote Object. Message is a Video Note, information about the Video Note'),
            'caption' => $this->text()->comment('For message with caption, the actual UTF-8 text of the caption'),
            'contact' => $this->text()->comment('Contact object. Message is a shared contact, information about the contact'),
            'location' => $this->text()->comment('Location object. Message is a shared location, information about the location'),
            'venue' => $this->text()->comment('Venue object. Message is a Venue, information about the Venue'),
            'poll' => $this->text()->comment('Poll object. Message is a native poll, information about the poll'),
            'dice' => $this->text()->comment('Message is a dice with random value from 1 to 6'),
            'new_chat_members' => $this->text()->comment('List of unique user identifiers, new member(s) were added to the group, information about them (one of these members may be the bot itself)'),
            'left_chat_member' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Unique user identifier, a member was removed from the group, information about them (this member may be the bot itself)'),
            'new_chat_title' => $this->char(255)->defaultValue(NULL)->comment('A chat title was changed to this value'),
            'new_chat_photo' => $this->text()->comment('Array of PhotoSize objects. A chat photo was change to this value'),
            'delete_chat_photo' => $this->tinyInteger(1)->defaultValue(0)->comment('Informs that the chat photo was deleted'),
            'group_chat_created' => $this->tinyInteger(1)->defaultValue(0)->comment('Informs that the group has been created'),
            'supergroup_chat_created' => $this->tinyInteger(1)->defaultValue(0)->comment('Informs that the supergroup has been created'),
            'channel_chat_created' => $this->tinyInteger(1)->defaultValue(0)->comment('Informs that the channel chat has been created'),
            'migrate_to_chat_id' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Migrate to chat identifier. The group has been migrated to a supergroup with the specified identifier'),
            'migrate_from_chat_id' => $this->bigInteger()->null()->defaultValue(NULL)->comment('Migrate from chat identifier. The supergroup has been migrated from a group with the specified identifier'),
            'pinned_message' => $this->text()->null()->comment('Message object. Specified message was pinned'),
            'invoice' => $this->text()->null()->comment('Message is an invoice for a payment, information about the invoice'),
            'successful_payment' => $this->text()->null()->comment('Message is a service message about a successful payment, information about the payment'),
            'connected_website' => $this->text()->null()->comment('The domain name of the website on which the user has logged in.'),
            'passport_data' => $this->text()->null()->comment('Telegram Passport data'),
            'reply_markup' => $this->text()->null()->comment('Inline keyboard attached to the message'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ENGINE=InnoDB');


        $this->addPrimaryKey('chat_id_id', '{{%telegram__message}}', ['chat_id', 'id']);

        $this->createIndex('user_id', '{{%telegram__message}}', 'user_id');
        $this->createIndex('forward_from', '{{%telegram__message}}', 'forward_from');
        $this->createIndex('forward_from_chat', '{{%telegram__message}}', 'forward_from_chat');
        $this->createIndex('reply_to_chat', '{{%telegram__message}}', 'reply_to_chat');
        $this->createIndex('reply_to_message', '{{%telegram__message}}', 'reply_to_message');
        $this->createIndex('left_chat_member', '{{%telegram__message}}', 'left_chat_member');
        $this->createIndex('migrate_from_chat_id', '{{%telegram__message}}', 'migrate_from_chat_id');
        $this->createIndex('migrate_to_chat_id', '{{%telegram__message}}', 'migrate_to_chat_id');


        $this->addForeignKey(
            'fk_user_id',
            '{{%telegram__message}}',
            'user_id',
            '{{%telegram__user}}',
            'id'
        );


        $this->addForeignKey(
            'fk_chat_id',
            '{{%telegram__message}}',
            'chat_id',
            '{{%telegram__chat}}',
            'id'
        );

        $this->addForeignKey(
            'fk_forward_from',
            '{{%telegram__message}}',
            'forward_from',
            '{{%telegram__user}}',
            'id'
        );


        $this->addForeignKey(
            'fk_forward_from_chat',
            '{{%telegram__message}}',
            'forward_from_chat',
            '{{%telegram__chat}}',
            'id'
        );


        $this->addForeignKey(
            'fk_reply_to_chat',
            '{{%telegram__message}}',
            ['reply_to_chat', 'reply_to_message'],
            '{{%telegram__message}}',
            ['chat_id', 'id']
        );

        $this->addForeignKey(
            'fk_left_chat_member',
            '{{%telegram__message}}',
            'left_chat_member',
            '{{%telegram__user}}',
            'id'
        );


    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%telegram__message}}');
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return "m000004_000000_telegram_message was reverted.\n";
    }

}
