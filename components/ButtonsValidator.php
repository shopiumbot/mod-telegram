<?php

namespace shopium\mod\telegram\components;

use panix\engine\CMS;
use Yii;
use yii\helpers\Html;
use yii\validators\UrlValidator;
use yii\validators\Validator;
use panix\engine\assets\ValidationAsset;

class ButtonsValidator extends Validator
{

    public function init()
    {
        if ($this->message == null) {
            $this->message = 'URL занят';
        }
        parent::init();
    }

    public function validateAttribute($model, $attribute)
    {
        $items = $model->$attribute;
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
            $validator = new UrlValidator();
            if ($validator->validate($item['url'], $error)) {
                echo 'Email is valid.';
            } else {
                $key = $attribute . ($multiple ? '[' . $index . '][url]' : '');
              //  $this->addError($model, $key, $error);
            }
            //$validator = new \yii\validators\NumberValidator();

            //$validator->validate($item, $error);
            if (!empty($error)) {
                $key = $attribute . ($multiple ? '[' . $index . '][label]' : '');
                // CMS::dump($key);die;
                $this->addError($model, $attribute, $error);
            }
        }
        $this->addError($model, 'buttons[0][url]', 'test');
    }

    public function clientValidateAttribute222($model, $attribute, $view)
    {
        /** @var \yii\web\View $view */
        ValidationAsset::register($view);
        $options = [
            'model' => get_class($model),
            'pk' => $model->primaryKey,
            'usexhr' => true,
            'successMessage' => $this->message,
            'AttributeSlug' => $attribute,
            'AttributeSlugId' => Html::getInputId($model, $attribute),
            'attributeCompareId' => Html::getInputId($model, $this->attributeCompare),
        ];
        if (Yii::$app->language == Yii::$app->languageManager->default->code) {
            $view->registerJs("init_translitter(" . json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ");");
        }
        return null;
    }

}
