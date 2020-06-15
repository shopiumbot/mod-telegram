<?php


namespace shopium\mod\telegram\widgets\editor;

use panix\engine\web\AssetBundle;


class EditorAsset extends AssetBundle
{

    public $sourcePath = '@npm/easymde/dist';

    public $js = [
        'easymde.min.js',
    ];
    public $css = [
        'easymde.min.css',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
