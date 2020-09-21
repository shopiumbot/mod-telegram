<?php

use panix\engine\widgets\Pjax;
use panix\engine\grid\GridView;
use panix\engine\Html;
use panix\engine\CMS;
use shopium\mod\telegram\models\search\MailingSearch;

/** @var \shopium\mod\telegram\components\Api $api */
$api = Yii::$app->telegram->getApi();
if (Yii::$app->session->hasFlash('telegram-error')) {
    foreach (Yii::$app->session->getFlash('telegram-error') as $error) {
        echo '<div class="alert alert-danger">' . $error . '</div>';
    }

}


$Viber = new \shopium\mod\telegram\models\ViberTest();
$s = $Viber->message_post(
    '380682937379',
    [
        'name' => 'Admin', // Имя отправителя. Максимум символов 28.
        'avatar' => 'http://avatar.example.com' // Ссылка на аватарку. Максимальный размер 100кб.
    ],
    'Test'
);
CMS::dump($s);die;
$dataPoll = [
    'chat_id' => 812367093,
    'question' => 'Test Poll',
    'is_anonymous' => false,
    'type' => 'regular', //quiz, regular
    'allows_multiple_answers' => false,
    //'options'=>['test','test2']
    'options' => json_encode([
        '123213','213213213213123'
    ])
];



/*  $results = Request::sendToActiveChats(
      'sendMessage', // Callback function to execute (see Request.php methods)
      ['text' => $chat->getFirstName().' '.$chat->getLastName().' @'.$chat->getUsername().'! go go go!'], // Param to evaluate the request
      [
          'groups'      => true,
          'supergroups' => true,
          'channels'    => false,
          'users'       => true,
      ]
  );*/
// echo $dataPoll['options'].PHP_EOL;

$pollRequest = \Longman\TelegramBot\Request::sendPoll($dataPoll);

if($pollRequest->getOk()){
  CMS::dump($pollRequest->getResult()->getPoll());die;



    $db = \Longman\TelegramBot\DB::insertPollRequest($pollRequest->getResult()->getPoll());
    CMS::dump($db);die;
}


Pjax::begin([
    'dataProvider' => $dataProvider
]);
echo GridView::widget([
    //'layoutPath' => '@user/views/layouts/_grid_layout',
    'tableOptions' => ['class' => 'table table-striped'],
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        [
            // 'header' => 'Type',
            'attribute' => 'type',
            'format' => 'raw',
            'filter' => Html::dropDownList(Html::getInputName(new MailingSearch, 'type'), (isset(Yii::$app->request->get('MailingSearch')['type'])) ? Yii::$app->request->get('MailingSearch')['type'] : null, \shopium\mod\telegram\models\Mailing::typeList(),
                [
                    'class' => 'form-control',
                    'prompt' => html_entity_decode('&mdash; тип &mdash;')
                ]
            ),
            'value' => function ($model) {
                return $model::typeList()[$model->type];
            }
        ],
        [
            // 'header' => 'text',
            'attribute' => 'text',
            'format' => 'raw',
            'value' => function ($model) {
                return $model->text;
            }
        ],
        [
            'attribute' => 'created_at',
            'format' => 'raw',
            'value' => function ($model) {
                return CMS::date($model->created_at);
            }
        ],
    ]
]);
Pjax::end();