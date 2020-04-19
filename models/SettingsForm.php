<?php

namespace shopium\mod\telegram\models;

use panix\engine\SettingsModel;

class SettingsForm extends SettingsModel
{

    public static $category = 'telegram';
    protected $module = 'telegram';

    public $api_token;
    public $bot_name;
    public $password;
    public $empty_cart_text;
    public $empty_history_text;
    public $bot_admins;

    public function rules()
    {
        return [
            [['api_token', 'bot_name', 'password', 'empty_cart_text', 'empty_history_text'], "required"],
            //  [['product_related_bilateral', 'seo_categories','group_attribute'], 'boolean'],
            //  [['seo_categories_title'], 'string', 'max' => 255],
            [['api_token', 'bot_name', 'password', 'empty_cart_text', 'empty_history_text', 'bot_admins'], 'string'],
        ];
    }


}
