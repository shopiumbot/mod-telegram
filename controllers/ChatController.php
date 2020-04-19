<?php

namespace shopium\mod\telegram\controllers;

use shopium\mod\telegram\Commands\YiiChatCommand;
use shopium\mod\telegram\models\Message;
use Longman\TelegramBot\Exception\TelegramException;
use yii\base\UserException;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class ChatController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'send-msg' => ['post'],
                    'get-all-messages' => ['post'],
                    'get-last-messages' => ['post'],
                ],
            ],
        ];
    }

    public function actionSendMsg()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $postData = \Yii::$app->request->post();

        try {
            $result = YiiChatCommand::sendToAuthorized($postData);
        } catch (TelegramException $e){
            throw new UserException($e->getMessage());
        }
       
        return $result;
    }

    public function actionGetAllMessages()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $session = \Yii::$app->session;
        if($session->has('tlgrm_chat_id')){
            $tlgrmChatId = $session->get('tlgrm_chat_id');
        }else{
            return false;
        }

        try {
            $messages = Message::find()->where(['client_chat_id' => $tlgrmChatId])->asArray()->all();
        } catch (TelegramException $e){
            throw new UserException('Messages load error');
        }
        if (!empty($messages)) return $messages;

        return false;
    }

    public function actionGetLastMessages()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $postData = \Yii::$app->request->post();
        $session = \Yii::$app->session;
        if($session->has('tlgrm_chat_id')){
            $tlgrmChatId = $session->get('tlgrm_chat_id');
        }else{
            return false;
        }
        try {
            $messages = Message::find()->where(['client_chat_id' => $tlgrmChatId])->andWhere(['>', 'time', $postData['lastMsgTime']])->asArray()->all();
        } catch (TelegramException $e){
            throw new UserException('Messages load error');
        }
        if (!empty($messages)) return $messages;

        return false;
    }
    
}
