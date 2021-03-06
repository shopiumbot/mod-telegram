<?php

namespace shopium\mod\telegram\controllers;

use shopium\mod\telegram\models\forms\SendAllMessageForm;
use Yii;
use yii\base\UserException;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use Longman\TelegramBot\Exception\TelegramException;
use core\components\controllers\AdminController;
use shopium\mod\telegram\models\forms\SendMessageForm;
use shopium\mod\telegram\models\search\MessageSearch;
use shopium\mod\telegram\components\Api;
use shopium\mod\telegram\models\Message;
use shopium\mod\telegram\models\User;

class MessageController extends AdminController
{

    public $icon = 'settings';



    public function actionIndex()
    {
        $this->layout = '@theme/views/layouts/dashboard_fluid';
        $this->pageName = Yii::t('app/default', 'SETTINGS');
        $this->view->params['breadcrumbs'] = [
            [
                'label' => $this->module->info['label'],
                'url' => $this->module->info['url'],
            ],
            $this->pageName
        ];
        $user_id = Yii::$app->request->get('user_id');
        if ($user_id) {
            $view = 'view';

            $user = User::find()->where(['id' => $user_id])->one();
            $query = Message::find()
                ->where(['chat_id' => $user_id])
                ->orderBy(['date' => SORT_DESC]);
            //->groupBy(['user_id','chat_id'])
            //  ->all();


            $provider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 25,
                    // 'defaultPageSize' =>(int)  $this->allowedPageLimit[0],
                    // 'pageSizeLimit' => $this->allowedPageLimit,
                ]
            ]);

        } else {
            $view = 'index';
            $user = false;
            $provider = false;
        }
        $searchModel = new MessageSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        $sendForm = new SendMessageForm();
        if (Yii::$app->request->get('user_id')) {
            $sendForm->user_id = Yii::$app->request->get('user_id');
        }
        if ($sendForm->load(Yii::$app->request->post())) {

            if ($sendForm->validate()) {
                $sendForm->send();

                return $this->refresh();
            } else {
                print_r($sendForm->errors);
                die;
            }

        }


        return $this->render($view, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'sendForm' => $sendForm,
            'provider' => $provider,
            'user' => $user,
        ]);
    }

    public function actionLoadChat()
    {
        $this->layout = '@theme/views/layouts/dashboard_fluid';
        $user_id = Yii::$app->request->get('user_id');
        $user = User::find()->where(['id' => $user_id])->one();
        $model = Message::find()
            ->where(['chat_id' => $user_id])
            ->limit(10)
            ->orderBy(['date' => SORT_DESC])
            //->groupBy(['user_id','chat_id'])
            ->all();


        return $this->renderAjax('load-chat', ['model' => $model, 'user' => $user]);
    }


    public function actionSet()
    {
        Yii::$app->response->format = Response::FORMAT_HTML;
        try {
            // Create Telegram API object
            $telegram = new Api;


            // Set webhook

            $result = $telegram->setWebHook(Yii::$app->user->webhookUrl);
            if ($result->isOk()) {
                //Если меняеться токет, следует очищать кеш картинок
                \core\modules\images\models\Image::updateAll(['telegram_file_id' => NULL], ['IS NOT', 'telegram_file_id', null]);
                Yii::$app->session->setFlash("success", Yii::t("telegram/default", 'WEBHOOK_SET_SUCCESS'));
                return $this->redirect(['/admin/admin/default/index']);
            }
        } catch (TelegramException $e) {
            return $e->getMessage();
        }
        return null;
    }

    /**
     * @return null|string
     * @throws ForbiddenHttpException
     */
    public function actionUnset()
    {

        Yii::$app->response->format = Response::FORMAT_HTML;
        if (\Yii::$app->user->isGuest) throw new ForbiddenHttpException();
        try {
            // Create Telegram API object
            $telegram = new Api;

            // Unset webhook
            $result = $telegram->deleteWebhook();

            if ($result->isOk()) {
                Yii::$app->session->setFlash("success", Yii::t("telegram/default", 'WEBHOOK_UNSET_SUCCESS'));
                return $this->redirect(['/admin/admin/default/index']);
            }
        } catch (TelegramException $e) {
            return $e->getMessage();
        }
    }


    public function actionSendAll(){

        $model = new SendAllMessageForm();
        if ($model->load(Yii::$app->request->post())) {

            if ($model->validate()) {
                $model->send();

                return $this->refresh();
            } else {
                print_r($model->errors);
                die;
            }

        }
        return $this->render('send-all', ['model' => $model]);
    }
}
