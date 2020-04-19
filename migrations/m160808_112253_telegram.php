<?php

namespace shopium\mod\telegram\migrations;

use yii\console\Exception;
use panix\engine\db\Migration;

class m160808_112253_telegram extends Migration
{
    public $settingsForm = 'shopium\mod\telegram\models\SettingsForm';
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {


        $this->loadSettings();

        $this->createTable('{{%tlgrm_actions}}', [
            'chat_id' => $this->primaryKey(),
            'action' => $this->string(62),
            'param' => $this->string(62)
        ]);


        $this->createTable('{{%tlgrm_messages}}', [
            'time' => $this->timestamp(),
            'client_chat_id' => $this->string(16)->notNull(),
            'message' => $this->string(4100),
            'direction' => $this->smallInteger(1)
        ]);
        $this->addPrimaryKey('tlgrm_messages_PK', '{{%tlgrm_messages}}', 'time');


        $this->createTable('{{%tlgrm_auth_mngr_chats}}', [
            'chat_id' => $this->integer(11),
            'client_chat_id' => $this->string(16)->unique(),
            'timestamp'=> $this->timestamp()
        ]);
        $this->addPrimaryKey('tlgrm_auth_mngr_chats_PK', '{{%tlgrm_auth_mngr_chats}}', 'chat_id');
        

        $this->createTable('{{%tlgrm_usernames}}', [
            'id' => $this->primaryKey()->notNull(),
            'chat_id' => $this->integer(11),
            'user_id' => $this->integer(11),
            'username' => $this->string(100)
        ]);
        $this->createIndex('tlgrm_usernames_uniq', '{{%tlgrm_usernames}}', ['chat_id', 'user_id', 'username']);
    }

    public function safeDown()
    {
        try {
            $this->dropTable('{{%tlgrm_actions}}');
            $this->dropTable('{{%tlgrm_auth_mngr_chats}}');
            $this->dropTable('{{%tlgrm_messages}}');
            $this->dropTable('{{%tlgrm_usernames}}');
        } catch (Exception $e){
            var_dump($e->getMessage());
            return false;
        }

        return "m160808_112253_telegram was reverted.\n";
    }
    
}
