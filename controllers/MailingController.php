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
        $this->breadcrumbs = [

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


        /*$this->breadcrumbs[] = [
            'label' => Yii::t('telegram/default', 'MODULE_NAME'),
            'url' => ['index']
        ];*/
        $this->breadcrumbs[] = [
            'label' => Yii::t('telegram/default', 'MAILING'),
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
            'address',
            'longitude',
            'latitude',
            'buttons',
            'send_to_groups', 'send_to_supergroups', 'send_to_channels', 'send_to_users', 'send_to_admins'
        ]);
        $dy_model->setAttributeLabels([
            'send_to_groups' => Yii::t('telegram/Mailing', 'SEND_TO_GROUPS'),
            'send_to_supergroups' => Yii::t('telegram/Mailing', 'SEND_TO_SUPERGROUPS'),
            'send_to_channels' => Yii::t('telegram/Mailing', 'SEND_TO_CHANNELS'),
            'send_to_users' => Yii::t('telegram/Mailing', 'SEND_TO_USERS'),
            'disable_notification' => Yii::t('telegram/Mailing', 'DISABLE_NOTIFICATION'),
            'send_to_admins'=>Yii::t('telegram/Mailing', 'SEND_TO_ADMINS'),
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

        ]);
        $dy_model->addRule(['disable_notification', 'send_to_groups', 'send_to_supergroups', 'send_to_channels', 'send_to_users','send_to_admins'], 'boolean')
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
                // $validator = new yii\validators\RequiredValidator();

                // $validator->validate($item['label'], $error);
                if(empty($item['callback'])){
                $validator = new UrlValidator();
                if (!$validator->validate($item['url'], $error)) {
                    $key = $attribute . ($multiple ? '[' . $index . '][url]' : '');
                    $dy_model->addError($key, $error);
                }
                }

                $validator2 = new RequiredValidator;
                if (!$validator2->validate($item['label'], $error)) {
                    $key = $attribute . ($multiple ? '[' . $index . '][label]' : '');
                    $dy_model->addError($key, $error);
                }
                //$validator = new \yii\validators\NumberValidator();

                //$validator->validate($item, $error);
               // if (!empty($error)) {
                  //  $key = $attribute . ($multiple ? '[' . $index . '][label]' : '');
                    // CMS::dump($key);die;
                  //  $dy_model->addError($key, $error);
              //  }
            }

        });



        if (in_array($model->type, ['sendPhoto', 'sendAudio', 'sendDocument'])) {
            $dy_model->addRule('media', 'string');
            $dy_model->addRule('media', 'required');
        }

        if ($model->type == 'sendDocument') {
            $dy_model->addRule('media', 'file', ['skipOnEmpty' => true]);
        } elseif ($model->type == 'sendPhoto') {
            $dy_model->addRule('media', 'file', ['skipOnEmpty' => true, 'extensions' => ['png', 'jpg']]);

        } elseif ($model->type == 'sendAudio') {
            $dy_model->addRule(['title','performer'], 'string');
            $dy_model->addRule('duration', 'integer');

            $dy_model->addRule('thumb', 'file', ['skipOnEmpty' => true, 'extensions' => ['jpg'], 'maxSize' => 200 * 1024]);
            $dy_model->addRule('thumb', 'string');
        } elseif ($model->type == 'sendMediaGroup') {
            // $dy_model->addRule('media', 'string');
            $dy_model->addRule('media', 'file', ['skipOnEmpty' => true, 'maxFiles' => 10, 'extensions' => ['png', 'jpg']]);
        } elseif ($model->type == 'sendVenue') {
            $dy_model->addRule(['latitude','longitude','address','title'], 'string');
            $dy_model->addRule(['latitude','longitude','address','title'], 'required');
        }

        if (in_array($model->type, ['sendDocument', 'sendPhoto', 'sendVideo'])) {
            $view = 'forms/_sendMedia';
        } elseif ($model->type == 'sendAudio') {
            $view = 'forms/_sendAudio';
        } elseif ($model->type == 'sendMediaGroup') {
            $view = 'forms/_sendMediaGroup';
        } elseif ($model->type == 'sendVenue') {
            $view = 'forms/_sendVenue';
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
                        $file->saveAs($path);
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
                }elseif($model->type == 'sendVenue'){
                    $model->title = $dy_model->title;
                    $model->address = $dy_model->address;
                    $model->longitude = $dy_model->longitude;
                    $model->latitude = $dy_model->latitude;
                }
                if($dy_model->buttons)
                    $model->buttons = json_encode($dy_model->buttons);

                if($dy_model->text)
                    $model->text = $dy_model->text;

                $model->disable_notification = $dy_model->disable_notification;
                $model->send_to_groups = $dy_model->send_to_groups;
                $model->send_to_supergroups = $dy_model->send_to_supergroups;
                $model->send_to_channels = $dy_model->send_to_channels;
                $model->send_to_users = $dy_model->send_to_users;
                $model->send_to_admins = $dy_model->send_to_admins;
                if($model->validate()){
                    $model->save(false);
                    Yii::$app->session->setFlash('success','Рассылка успешно отправлена.');
                    return $this->redirect(['index']);
                }else{
                    print_r($model->getErrors());die;
                }

                //   return $this->redirectPage($isNew, $post);
            }else{
                print_r($dy_model->getErrors());die;
            }
        }

        return $this->render('update', ['model' => $model, 'view' => $view, 'dy_model' => $dy_model]);
    }
}