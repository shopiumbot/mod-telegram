<?php

namespace shopium\mod\telegram\models;

use app\modules\user\components\SettingsModel;
use Yii;
class SettingsForm extends SettingsModel
{

    public static $category = 'telegram';

    protected $module = 'telegram';

    public $api_token;
    public $bot_name;
    public $bot_admins;

    public function init()
    {

        parent::init();
    }

    public function rules()
    {
        return [
            [['api_token', 'bot_name'], "required"],
            [['api_token', 'bot_name'], 'string'],
        ];
    }


    /**
     * @inheritdoc
     */
    public static function defaultSettings()
    {
        return [
            'bot_admins' => '',
        ];
    }

}
