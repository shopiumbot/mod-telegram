<?php

namespace shopium\mod\telegram\controllers;


use Longman\TelegramBot\DB;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\telegram\components\Api;
use shopium\mod\telegram\models\Chat;
use Yii;
use core\components\controllers\AdminController;
use shopium\mod\telegram\models\forms\SendMessageForm;
use shopium\mod\telegram\models\Mailing;
use shopium\mod\telegram\models\search\MailingSearch;
use yii\web\UploadedFile;

class MailingController extends AdminController
{

    public $icon = 'd';

    public function actionIndex()
    {

        $this->pageName = Yii::t('telegram/default', 'MAILING');
        $this->breadcrumbs = [
            [
                'label' => $this->module->info['label'],
                'url' => $this->module->info['url'],
            ],
            $this->pageName
        ];
        $this->buttons = [
            [
                'label' => Yii::t('telegram/Mailing','CREATE'),
                'url' => ['create'],
                'options' => ['class' => 'btn btn-success']
            ],
        ];
        $searchModel = new MailingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        $sendForm = new SendMessageForm();
        if ($sendForm->load(Yii::$app->request->post())) {
            if ($sendForm->validate()) {
                $sendForm->send();
                return $this->refresh();
            }

        }
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'sendForm' => $sendForm
        ]);
    }

    public function actionUpdate($id = false)
    {
        $model = Mailing::findModel($id, Yii::t('telegram/default', 'no found mailing'));

        if (isset(Yii::$app->request->get('Mailing')['type'])) {
            $model->type = Yii::$app->request->get('Mailing')['type'];
        }

        $this->pageName = ($model->isNewRecord) ? Yii::t('telegram/default', 'Создание рассылки') :
            Yii::t('telegram/default', 'Редактирование рассылки');


        $this->breadcrumbs[] = [
            'label' => Yii::t('telegram/default', 'MODULE_NAME'),
            'url' => ['index']
        ];
        $this->breadcrumbs[] = $this->pageName;


        $dy_model = new \yii\base\DynamicModel([
            'type',
            'text',
            'disable_notification',
            'media',
            'title',
            'performer',
            'duration',
            'thumb',
            'send_to_groups', 'send_to_supergroups', 'send_to_channels', 'send_to_users'
        ]);
        $dy_model->setAttributeLabels([
            'send_to_groups' => Yii::t('telegram/Mailing', 'SEND_TO_GROUPS'),
            'send_to_supergroups' => Yii::t('telegram/Mailing', 'SEND_TO_SUPERGROUPS'),
            'send_to_channels' => Yii::t('telegram/Mailing', 'SEND_TO_CHANNELS'),
            'send_to_users' => Yii::t('telegram/Mailing', 'SEND_TO_USERS'),
            'disable_notification' => Yii::t('telegram/Mailing', 'DISABLE_NOTIFICATION'),
            'text' => Yii::t('telegram/Mailing', 'TEXT'),
            'media' => Yii::t('telegram/Mailing', 'MEDIA'),
            'title' => Yii::t('telegram/Mailing', 'TITLE'),
            'duration' => Yii::t('telegram/Mailing', 'DURATION'),
            'performer' => Yii::t('telegram/Mailing', 'PERFORMER'),
            'thumb' => Yii::t('telegram/Mailing', 'THUMB'),
        ]);
        $dy_model->addRule(['disable_notification', 'send_to_groups', 'send_to_supergroups', 'send_to_channels', 'send_to_users'], 'boolean')
            ->addRule('text', 'string');


        if (in_array($model->type,['sendPhoto','sendAudio','sendDocument'])) {
            $dy_model->addRule('media', 'string');
            $dy_model->addRule('media', 'required');
        }

        if ($model->type == 'sendDocument') {
            $dy_model->addRule('media', 'file', ['skipOnEmpty' => true]);
        } elseif ($model->type == 'sendPhoto') {
            $dy_model->addRule('media', 'file', ['skipOnEmpty' => true, 'extensions' => ['png', 'jpg']]);

        } elseif ($model->type == 'sendAudio') {
            $dy_model->addRule('title', 'string');
            $dy_model->addRule('duration', 'integer');
            $dy_model->addRule('performer', 'string');

            $dy_model->addRule('thumb', 'file', ['skipOnEmpty' => true, 'extensions' => ['jpg'], 'maxSize' => 200*1024]);
            $dy_model->addRule('thumb', 'string');
        }
        if (in_array($model->type, ['sendDocument', 'sendPhoto'])) {
            $view = 'forms/_sendMedia';
        } elseif ($model->type == 'sendAudio') {
            $view = 'forms/_sendAudio';
        } elseif ($model->type == 'sendMediaGroup') {
            $view = 'forms/_sendMediaGroup';
        } else {
            $view = 'forms/_sendMessage';
        }
        /* $dy_model->addRule(['text','email'], 'required')
             ->addRule(['email'], 'email')
             ->addRule('address', 'string',['max'=>32]);*/


        $post = Yii::$app->request->post();

        if ($dy_model->load($post)) {
            $media = UploadedFile::getInstance($dy_model, 'media');
            $thumb = UploadedFile::getInstance($dy_model, 'thumb');


            if ($media) {
                $path = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . $media->getBaseName() . '.' . $media->extension;
                $media->saveAs($path);
                $model->media = $path;
                $dy_model->media = $path;
            }
            if ($thumb) {
                $path = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . $thumb->getBaseName() . '.' . $thumb->extension;
                $thumb->saveAs($path);
                $model->thumb = $path;
            }
            if ($dy_model->validate()) {






                if ($model->type == 'sendAudio') {
                    $model->title = $dy_model->title;
                    $model->duration = $dy_model->duration;
                    $model->performer = $dy_model->performer;
                }
                $model->text = $dy_model->text;
                $model->disable_notification = $dy_model->disable_notification;
                $model->send_to_groups = $dy_model->send_to_groups;
                $model->send_to_supergroups = $dy_model->send_to_supergroups;
                $model->send_to_channels = $dy_model->send_to_channels;
                $model->send_to_users = $dy_model->send_to_users;
                $model->save(false);
                //   return $this->redirectPage($isNew, $post);
            }
        }

        return $this->render('update', ['model' => $model, 'view' => $view, 'dy_model' => $dy_model]);
    }
}
