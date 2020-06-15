<?php

namespace shopium\mod\telegram\widgets\editor;
//https://github.com/Ionaru/easy-markdown-editor/issues/207

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

class EditorInput extends InputWidget
{


    public $language;

    public $clientOptions = [];

    public function init()
    {
        parent::init();
        // $this->assetsPlugins = Yii::$app->getAssetManager()->publish(Yii::getAlias("@vendor/panix/wgt-tinymce/plugins"));


        $defaultClientOptions = [];
        $lang = Yii::$app->language;

        $defaultClientOptions['status'] = false;
        // $defaultClientOptions['contextmenu'] = "link image inserttable | cell row column deletetable";

        //$defaultClientOptions['element'] = "#{$this->options['id']}";
        $defaultClientOptions['element'] = new JsExpression("document.getElementById('{$this->options['id']}')");
        $defaultClientOptions['toolbar'] = [
            [
                'name' => "bold",
                'action' => new JsExpression("EasyMDE.toggleBold"),
                'className' => "fa fa-bold",
                'title' => "Bold",
            ],
            [
                'name' => "bold2",
                'action' => new JsExpression("function customFunction(editor){
				    console.log(editor);
                }"),
                'className' => "fa fa-bold",
                'title' => "Bold",
            ],
            "italic",
            'strikethrough',
            'link',
            'image',
            'code'
        ];
        $defaultClientOptions['spellChecker'] = false;

        $defaultClientOptions['blockStyles'] = [
            'bold' => "*",
            'italic' => "_",
            'strikethrough' => '~'
        ];

        $view = $this->getView();

        $this->clientOptions = ArrayHelper::merge($defaultClientOptions, $this->clientOptions);

    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }


        $this->registerClientScript();
    }

    /**
     * Registers tinyMCE js plugin
     */
    protected function registerClientScript()
    {
        $js = [];
        $view = $this->getView();
        EditorAsset::register($view);

        $options = Json::encode($this->clientOptions);

        $js[] = "var easymde = new EasyMDE($options);";
        $view->registerJs(implode("\n", $js));
    }

}
