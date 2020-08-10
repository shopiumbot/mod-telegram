<?php

namespace shopium\mod\telegram;

use panix\engine\web\AssetBundle;

class Asset extends AssetBundle
{
    public $sourcePath = __DIR__.'/assets';
    public $js = [
        //'js/products.js',
        // 'js/products.index.js',
    ];

    /* public $depends = [
           'panix\engine\assets\TinyMceAsset'
       ];*/
}
