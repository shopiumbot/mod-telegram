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
use yii\helpers\FileHelper;
use yii\validators\RequiredValidator;
use yii\validators\UrlValidator;
use yii\web\UploadedFile;

class MailingController extends AdminController
{

    public $icon = 'd';

    public function actionIndex()
    {

        $this->pageName = Yii::t('telegram/default', 'MAILING');
        $this->view->params['breadcrumbs'] = [

            $this->pageName
        ];
        $this->buttons = [
            [
                'label' => Yii::t('telegram/Mailing', 'CREATE'),
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


        /*$this->view->params['breadcrumbs'][] = [
            'label' => Yii::t('telegram/default', 'MODULE_NAME'),
            'url' => ['index']
        ];*/
        $this->view->params['breadcrumbs'][] = [
            'label' => Yii::t('telegram/default', 'MAILING'),
            'url' => ['index']
        ];
        $this->view->params['breadcrumbs'][] = $this->pageName;


        $dy_model = new \yii\base\DynamicModel([
            'type',
            'text',
            'disable_notification',
            'media',
            'title',
            'performer',
            'duration',
            'thumb',
            'address',
            'longitude',
            'latitude',
            'buttons',
            'phone_number',
            'first_name',
            'last_name',
            'poll_options',
            'poll_question',
            'poll_is_anonymous',
            'poll_type',
            'poll_allows_multiple_answers',
            'send_to_groups', 'send_to_supergroups', 'send_to_channels', 'send_to_users', 'send_to_admins'
        ]);

        $dy_model->setAttributeLabels([
            'send_to_groups' => Yii::t('telegram/Mailing', 'SEND_TO_GROUPS', [Chat::find()->where(['type' => 'group'])->count()]),
            'send_to_supergroups' => Yii::t('telegram/Mailing', 'SEND_TO_SUPERGROUPS', [Chat::find()->where(['type' => 'supergroup'])->count()]),
            'send_to_channels' => Yii::t('telegram/Mailing', 'SEND_TO_CHANNELS', [Chat::find()->where(['type' => 'channel'])->count()]),
            'send_to_users' => Yii::t('telegram/Mailing', 'SEND_TO_USERS', [Chat::find()->where(['type' => 'private'])->count()]),
            'disable_notification' => Yii::t('telegram/Mailing', 'DISABLE_NOTIFICATION'),
            'send_to_admins' => Yii::t('telegram/Mailing', 'SEND_TO_ADMINS', [count(Yii::$app->user->getBotAdmins())]),
            'text' => Yii::t('telegram/Mailing', 'TEXT'),
            'media' => Yii::t('telegram/Mailing', 'MEDIA'),
            'title' => Yii::t('telegram/Mailing', 'TITLE'),
            'duration' => Yii::t('telegram/Mailing', 'DURATION'),
            'performer' => Yii::t('telegram/Mailing', 'PERFORMER'),
            'thumb' => Yii::t('telegram/Mailing', 'THUMB'),
            'longitude' => Yii::t('telegram/Mailing', 'LONGITUDE'),
            'latitude' => Yii::t('telegram/Mailing', 'LATITUDE'),
            'address' => Yii::t('telegram/Mailing', 'ADDRESS'),
            'buttons' => Yii::t('telegram/Mailing', 'buttons'),

            'phone_number' => Yii::t('telegram/Mailing', 'phone_number'),
            'first_name' => Yii::t('telegram/Mailing', 'first_name'),
            'last_name' => Yii::t('telegram/Mailing', 'last_name'),
            'poll_options' => Yii::t('telegram/Mailing', 'poll_options'),
            'poll_question' => Yii::t('telegram/Mailing', 'question'),

        ]);
        $dy_model->addRule(['disable_notification', 'send_to_groups', 'send_to_supergroups', 'send_to_channels', 'send_to_users', 'send_to_admins'], 'boolean')
            ->addRule('text', 'string');


        //$dy_model->addRule('buttons', 'each', ['rule' => ['string']]);
        $dy_model->addRule('thumb', 'default');
        //  $dy_model->addRule('buttons', '\shopium\mod\telegram\components\ButtonsValidator');

        $dy_model->addRule('buttons', function ($attribute, $params, $validator) use ($dy_model) {


            $items = $dy_model->$attribute;
            if (!is_array($items)) {
                $items = [];
            }

            $multiple = $items;
            if (!is_array($items)) {
                $multiple = false;
                $items = (array)$items;
            }
            foreach ($items as $index => $item) {

                $error = null;
                $validator = new RequiredValidator();
                $validator->validate($item['callback'], $error);


                $isUrl = preg_match('/http(s?)\:\/\//i', $item['callback']);

                if ($isUrl) {
                    $validator = new UrlValidator();
                    if (!$validator->validate($item['callback'], $error)) {
                        $key = $attribute . ($multiple ? '[' . $index . '][callback]' : '');
                        $dy_model->addError($key, 'zzzzz');
                    }
                } else {

                    if (!preg_match('/getList|checkOut|getCart|getHistory|getProduct|addCart/iu', $item['callback'], $match)) {
                        $key = $attribute . ($multiple ? '[' . $index . '][callback]' : '');
                        $dy_model->addError($key, 'Не доступный callback');
                    }

                }

                $validator2 = new RequiredValidator;
                if (!$validator2->validate($item['label'], $error)) {
                    $key = $attribute . ($multiple ? '[' . $index . '][label]' : '');
                    $dy_model->addError($key, $error);
                }
            }

        });



        if (in_array($model->type, ['sendPhoto', 'sendAudio', 'sendDocument', 'sendVideo'])) {
            $dy_model->addRule('media', 'string');
            $dy_model->addRule('media', 'required');
        }

        if ($model->type == 'sendDocument') {
            $dy_model->addRule('media', 'file', ['maxSize' => 1024 * 1024 * 50, 'skipOnEmpty' => true]);
        } elseif ($model->type == 'sendMessage') {
            $dy_model->addRule('text', 'required');
        } elseif ($model->type == 'sendPoll') {
            $dy_model->addRule(['poll_question','poll_options'], 'required');
            $dy_model->addRule(['poll_is_anonymous','poll_allows_multiple_answers'], 'boolean');


            $dy_model->addRule('poll_options', function ($attribute, $params, $validator) use ($dy_model) {


                $items = $dy_model->$attribute;
                if (!is_array($items)) {
                    $items = [];
                }

                $multiple = $items;
                if (!is_array($items)) {
                    $multiple = false;
                    $items = (array)$items;
                }

                foreach ($items['option'] as $index => $item) {

                    $error = null;
                    /*$validator = new RequiredValidator();
                    $validator->validate($item['callback'], $error);*/

                    $validator2 = new RequiredValidator;
                    if (!$validator2->validate($item, $error)) {
                        $key = $attribute . ($multiple ? '[' . $index . ']' : '');
                        $dy_model->addError($key, $error);
                    }
                }

            });



        } elseif ($model->type == 'sendPhoto') {
            $dy_model->addRule('media', 'file', ['maxSize' => 1024 * 1024 * 10, 'skipOnEmpty' => true, 'extensions' => ['png', 'jpg']]);
        } elseif ($model->type == 'sendVideo') {
            $dy_model->addRule('media', 'file', ['maxSize' => 1024 * 1024 * 50, 'skipOnEmpty' => true, 'extensions' => ['mp4', 'avi']]);
        } elseif ($model->type == 'sendContact') {
            $dy_model->addRule(['phone_number', 'first_name'], 'required');
            $dy_model->addRule(['first_name', 'last_name', 'phone_number'], 'string', ['max' => 50]);
        } elseif ($model->type == 'sendAudio') {
            $dy_model->addRule(['title', 'performer'], 'string');
            $dy_model->addRule('duration', 'integer');
            $dy_model->addRule('thumb', 'file', ['maxSize' => 1024 * 1024 * 50, 'skipOnEmpty' => true, 'extensions' => ['jpg']]);
            $dy_model->addRule('thumb', 'string');
        } elseif ($model->type == 'sendMediaGroup') {
            // $dy_model->addRule('media', 'string');
            $dy_model->addRule('media', 'file', ['maxSize' => 1024 * 1024 * 50, 'skipOnEmpty' => true, 'maxFiles' => 10, 'extensions' => ['png', 'jpg']]);
        } elseif ($model->type == 'sendVenue') {
            $dy_model->addRule(['latitude', 'longitude', 'address', 'title'], 'string');
            $dy_model->addRule(['latitude', 'longitude', 'address', 'title'], 'required');
        }

        if (in_array($model->type, ['sendDocument', 'sendPhoto', 'sendVideo'])) {
            $view = 'forms/_sendMedia';
        } elseif ($model->type == 'sendAudio') {
            $view = 'forms/_sendAudio';
        } elseif ($model->type == 'sendMediaGroup') {
            $view = 'forms/_sendMediaGroup';
        } elseif ($model->type == 'sendContact') {
            $view = 'forms/_sendContact';
        } elseif ($model->type == 'sendVenue') {
            $view = 'forms/_sendVenue';
        } elseif ($model->type == 'sendPoll') {
            $view = 'forms/_sendPoll';
        } else {
            $view = 'forms/_sendMessage';
        }
        /* $dy_model->addRule(['text','email'], 'required')
             ->addRule(['email'], 'email')
             ->addRule('address', 'string',['max'=>32]);*/


        $post = Yii::$app->request->post();

        if ($dy_model->load($post)) {


            // foreach ($dy_model->media as $file) {
            //     $media = UploadedFile::getInstance($dy_model, 'media');
            // }
            if ($model->type == 'sendMediaGroup') {
                $media = UploadedFile::getInstances($dy_model, 'media');
            } else {
                $media = UploadedFile::getInstance($dy_model, 'media');
            }
            //// CMS::dump($post);
            // CMS::dump($media);
            // die;
            $thumb = UploadedFile::getInstance($dy_model, 'thumb');

            if (!file_exists(Yii::getAlias('@uploads/tmp'))) {
                FileHelper::createDirectory(Yii::getAlias('@uploads/tmp'), 750);
            }
            if ($media) {
                if ($model->type == 'sendMediaGroup') {
                    foreach ($media as $file) {
                        $path = Yii::getAlias('@uploads/tmp') . DIRECTORY_SEPARATOR . $file->getBaseName() . '.' . $file->extension;
                        $file->saveAs($path); //comment for attach://
                        // $model->media[] = $file; //for attach://
                        $model->media[] = basename($path);
                        //  $dy_model->media[] = $path;
                    }
                } else {

                    $path = Yii::getAlias('@uploads/tmp') . DIRECTORY_SEPARATOR . $media->getBaseName() . '.' . $media->extension;
                    $media->saveAs($path);
                    $model->media[] = basename($path);
                    $dy_model->media = $path;
                }

            }
            if ($thumb) {
                $path = Yii::getAlias('@uploads/tmp') . DIRECTORY_SEPARATOR . $thumb->getBaseName() . '.' . $thumb->extension;
                $thumb->saveAs($path);
                $model->thumb = basename($path);
            }
            if ($dy_model->validate()) {


                if ($model->type == 'sendAudio') {
                    $model->title = $dy_model->title;
                    $model->duration = $dy_model->duration;
                    $model->performer = $dy_model->performer;
                } elseif ($model->type == 'sendVenue') {
                    $model->title = $dy_model->title;
                    $model->address = $dy_model->address;
                    $model->longitude = $dy_model->longitude;
                    $model->latitude = $dy_model->latitude;
                } elseif ($model->type == 'sendContact') {
                    $model->first_name = $dy_model->first_name;
                    $model->last_name = $dy_model->last_name;
                    $model->phone_number = $dy_model->phone_number;
                } elseif ($model->type == 'sendPoll') {
                    $model->poll_question = $dy_model->poll_question;

                    $model->poll_options = json_encode($dy_model->poll_options);

                    if ($dy_model->poll_is_anonymous)
                        $model->poll_is_anonymous = $dy_model->poll_is_anonymous;
                }
                if ($dy_model->buttons)
                    $model->buttons = json_encode($dy_model->buttons);

                if ($dy_model->text)
                    $model->text = $dy_model->text;

                $model->disable_notification = $dy_model->disable_notification;
                $model->send_to_groups = $dy_model->send_to_groups;
                $model->send_to_supergroups = $dy_model->send_to_supergroups;
                $model->send_to_channels = $dy_model->send_to_channels;
                $model->send_to_users = $dy_model->send_to_users;
                $model->send_to_admins = $dy_model->send_to_admins;
                if ($model->validate()) {
                    $model->save(false);
                    Yii::$app->session->setFlash('success', $model::t('SUCCESS_SEND'));
                    return $this->redirect(['index']);
                } else {
                    print_r($model->getErrors());
                    die;
                }

                //   return $this->redirectPage($isNew, $post);
            } else {
                // echo 'ee';
               // print_r($dy_model->getErrors());
               //   die;
            }
        }

        return $this->render('update', ['model' => $model, 'view' => $view, 'dy_model' => $dy_model]);
    }
}
