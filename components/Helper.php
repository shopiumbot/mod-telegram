<?php

namespace shopium\mod\telegram\components;

use panix\engine\CMS;
use yii\helpers\Html;

class Helper
{

    public static function parseMarkdown2($text, $entities)
    {
        $new =$text;
        if ($entities) {

            $entities = json_decode($entities);
            foreach ($entities as $entity) {
                if($entity->type=='url'){
                    $get = mb_substr($text,$entity->offset-2,$entity->length,'UTF-8');

                    $new = self::mb_substr_replace($text, Html::a($get,$get), $entity->offset-2, $entity->length);
                }
                if($entity->type=='mention'){
                    $get = mb_substr($text,$entity->offset,$entity->length);
                    // $text .= 'tg://resolve?domain='.$get;

                    $new = self::mb_substr_replace($text, Html::a('@'.$get,'tg://resolve?domain='.$get), $entity->offset-1, $entity->length);
                }
            }
            // CMS::dump($entities);
            // die;
        }
        return $new;
    }


    public static function parseMarkdown($text, $entities)
    {
        return $text;
        $new =$text;
        if ($entities) {

            $entities = json_decode($entities);
            foreach ($entities as $entity) {
                if($entity->type=='url'){
                    $get = mb_substr($text,$entity->offset-2,$entity->length);

                    $new = substr_replace($text, Html::a($get,$get), $entity->offset-2, $entity->length);
                }
                if($entity->type=='mention'){
                    $get = mb_substr($text,$entity->offset,$entity->length);
                    // $text .= 'tg://resolve?domain='.$get;

                    $new = substr_replace($text, Html::a('@'.$get,'tg://resolve?domain='.$get), $entity->offset-1, $entity->length);
                }
            }
            // CMS::dump($entities);
            // die;
        }
        return $new;
    }

    private static function mb_substr_replace($output, $replace, $posOpen, $posClose) {
        return mb_substr($output, 0, $posOpen).$replace.mb_substr($output, $posClose+1);
    }
}