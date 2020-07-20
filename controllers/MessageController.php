<?php

namespace shopium\mod\telegram\controllers;

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

    public $layout = '@theme/views/layouts/dashboard_fluid';

    public function actionIndex()
    {

        $this->pageName = Yii::t('app/default', 'SETTINGS');
        $this->breadcrumbs = [
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

            /*if (!empty(\Yii::$app->modules['telegram']->userCommandsPath)) {
                if (!$commandsPath = realpath(\Yii::getAlias(\Yii::$app->modules['telegram']->userCommandsPath))) {
                    $commandsPath = realpath(\Yii::getAlias('@app') . \Yii::$app->modules['telegram']->userCommandsPath);
                }

                if (!is_dir($commandsPath)) throw new UserException('dir ' . \Yii::$app->modules['telegram']->userCommandsPath . ' not found!');
            }*/

            // Set webhook

            $result = $telegram->setWebHook(Yii::$app->user->webhookUrl);
            if ($result->isOk()) {
                Yii::$app->session->setFlash("success-webhook", Yii::t("telegram/default", 'Бот успешно подписан'));
                return $this->redirect(['/admin']);
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
                Yii::$app->session->setFlash("success-webhook", Yii::t("telegram/default", 'Бот успешно отписан'));
                return $this->redirect(['/admin']);
            }
        } catch (TelegramException $e) {
            return $e->getMessage();
        }
    }
}
