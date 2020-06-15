<?php

namespace shopium\mod\telegram\models\forms;


use Longman\TelegramBot\Entities\BotCommand;
use Longman\TelegramBot\Request;
use Yii;
use yii\base\Model;

class CommandsForm extends Model
{

    public $data;

    public function init()
    {
        parent::init();

        $cmd = Request::getMyCommands()->getRawData();
        if ($cmd['ok']) {
            $this->data = $cmd['result'];
        }
    }

    public function rules()
    {
        return [
            ['data', 'validateCommands', 'skipOnEmpty' => false],
        ];
    }

    public function validateCommands($attribute)
    {
        // $requiredValidator = new RequiredValidator();
        $attributes = Yii::$app->request->post(__CLASS__);
        if (isset($attributes['data'])) {
            foreach ($attributes['data'] as $index => $row) {
                $error = null;
                foreach (['command'] as $name) { //, 'name'
                    $error = null;
                    $value = isset($row[$name]) ? $row[$name] : null;
                    // $requiredValidator->validate($value, $error);
                    if (!empty($error)) {
                        $key = $attribute . '[' . $index . '][' . $name . ']';
                        // echo $key;
                        $this->addError($key, $error);
                    }
                }
            }
        }
    }

    public function save()
    {
        $commands = [];
        if ($this->data) {
            foreach ($this->data as $data) {
                $commands[] = new BotCommand([
                    'command' => $data['command'],
                    'description' => $data['description']
                ]);
            }
        }
        Request::setMyCommands(['commands' => $commands]);


    }

    public function attributeLabels()
    {
        return [
            'data' => 'Data',
        ];
    }
}
