<?php

namespace shopium\mod\telegram\components;

use Longman\TelegramBot\Entities\Entity;
use panix\engine\CMS;
use yii\helpers\Html;

class Helper
{
    public static function Test($text){

        $bb[] = "/<\/p>/si";
        $html[] = "\n";
        $bb[] = "/<p>/si";
        $html[] = "";
        $bb[] = "/<br>/si";
        $html[] = "\n";
        $bb[] = "/<br\/>/si";
        $html[] = "\n";
        $bb[] = "/<br \/>/si";
        $html[] = "\n";
        $bb[] = "/&nbsp;|\u00a0/si";
        $html[] = " ";


       /* $bb[] = "/\"/si";
        $html[] = '&quot;';

        $bb[] = "/</si";
        $html[] = '&lt;';

        $bb[] = "/>/si";
        $html[] = '&gt;';

        $bb[] = "/&/si";
        $html[] = '&amp;';*/




        $bb[] = '/<span style=\"text-decoration: ?line-through;\">(.*?)<\/span>/si';
        $html[] = "<s>$1</s>";


        $bb[] = '/<span style=\"text-decoration: ?underline;\">(.*?)<\/span>/si';
        $html[] = "<u>$1</u>";
        $bb[] = '/<span class=\"underline\">(.*?)<\/span>/si';
        $html[] = "<u>$1</u>";




        $source = str_replace(array("&#034;", "&#039;", "&#092;"), array("\"", "'", "\\"), preg_replace($bb, $html, $text));
       // $source = preg_replace($bb, $html, $text);
        return $source;
    }


    public static function bbcode2Markdown($text){

        $bb[] = "#\[b\](.*?)\[/b\]#si";
        $html[] = "*\\1*";
        $bb[] = "#\[i\](.*?)\[/i\]#si";
        $html[] = "_\\1_";
        $bb[] = "#\[u\](.*?)\[/u\]#si";
        $html[] = "__\\1__";
        $bb[] = "#\[s\](.*?)\[/s\]#si";
        $html[] = "~\\1~";

        $bb[] = "#\[code\](.*?)\[/code\]#si";
        $html[] = "`\\1`";
        /* $bb[] = "#\[li\]#si";
         $html[] = "&bull; ";
         $bb[] = "#\[hr\]#si";
         $html[] = "<hr>";
         $bb[] = "#\*(\d{2})#";*/

        $source = str_replace(array("&#034;", "&#039;", "&#092;"), array("\"", "'", "\\"), preg_replace($bb, $html, $text));
        return $source;
    }

    public static function parseMarkdown2($text, $entities)
    {
        $new = $text;
        if ($entities) {

            $entities = json_decode($entities);
            foreach ($entities as $entity) {
                if ($entity->type == 'url') {
                    $get = mb_substr($text, $entity->offset - 2, $entity->length, 'UTF-8');

                    $new = self::mb_substr_replace($text, Html::a($get, $get), $entity->offset - 2, $entity->length);
                }
                if ($entity->type == 'mention') {
                    $get = mb_substr($text, $entity->offset, $entity->length);
                    // $text .= 'tg://resolve?domain='.$get;

                    $new = self::mb_substr_replace($text, Html::a('@' . $get, 'tg://resolve?domain=' . $get), $entity->offset - 1, $entity->length);
                }
            }
            // CMS::dump($entities);
            // die;
        }
        return $new;
    }

    private function parseEntitiesString($text, $entities)
    {
        $global_incr = 0;
        foreach ($entities as $entity) {
            if ($entity->getType() == 'italic') {
                $start = $global_incr + $entity->getOffset();
                $end = 1 + $start + $entity->getLength();

                $text = $this->mb_substr_replace($text, '_', $start, 0);
                $text = $this->mb_substr_replace($text, '_', $end, 0);

                $global_incr = $global_incr + 2;
            } elseif ($entity->getType() == 'bold') {
                $start = $global_incr + $entity->getOffset();
                $end = 1 + $start + $entity->getLength();

                $text = $this->mb_substr_replace($text, '*', $start, 0);
                $text = $this->mb_substr_replace($text, '*', $end, 0);

                $global_incr = $global_incr + 2;
            } elseif ($entity->getType() == 'code') {
                $start = $global_incr + $entity->getOffset();
                $end = 1 + $start + $entity->getLength();

                $text = $this->mb_substr_replace($text, '`', $start, 0);
                $text = $this->mb_substr_replace($text, '`', $end, 0);

                $global_incr = $global_incr + 2;
            } elseif ($entity->getType() == 'pre') {
                $start = $global_incr + $entity->getOffset();
                $end = 3 + $start + $entity->getLength();

                $text = $this->mb_substr_replace($text, '```', $start, 0);
                $text = $this->mb_substr_replace($text, '```', $end, 0);

                $global_incr = $global_incr + 6;
            } elseif ($entity->getType() == 'text_link') {
                $start = $global_incr + $entity->getOffset();
                $end = 1 + $start + $entity->getLength();
                $url = '(' . $entity->getUrl() . ')';

                $text = $this->mb_substr_replace($text, '[', $start, 0);
                $text = $this->mb_substr_replace($text, ']' . $url, $end, 0);

                $global_incr = $global_incr + 2 + mb_strlen($url);
            } elseif ($entity->getType() == 'code') {
                $start = $global_incr + $entity->getOffset();

                $text = mb_substr($text, 0, $start);
            }
        }

        return $text;
    }

    public static function parseMarkdown($text, $entities)
    {

        $global_incr = 0;

        $new = $text;
        $tester = '';
        if ($entities) {

            $entities = json_decode($entities);
            foreach ($entities as $entity) {
                if ($entity->type == 'url2') {
                    $get = mb_substr($text, $entity->offset - 2, $entity->length);

                    $new = substr_replace($text, Html::a($get, $get), $entity->offset - 2, $entity->length);
                }
                if ($entity->type == 'mention2') {
                    $get = mb_substr($text, $entity->offset, $entity->length);
                    // $text .= 'tg://resolve?domain='.$get;

                    $new = substr_replace($text, Html::a('@' . $get, 'tg://resolve?domain=' . $get), $entity->offset - 1, $entity->length);
                }
                if ($entity->type == 'bold2') {
                    $get = mb_substr($text, $entity->offset, $entity->length);
                    // $text .= 'tg://resolve?domain='.$get;
                    $text = self::mb_substr_replace($text, '*', $entity->offset, $entity->offset + $entity->length);
                    //$new = substr_replace($text, $get, $entity->offset, $entity->length);
                    // return $tester;
                }
            }
            // CMS::dump($entities);
            // die;
        }
        return Entity::escapeMarkdown($new);
    }

    private static function mb_substr_replace($output, $replace, $posOpen, $posClose)
    {
        return mb_substr($output, 0, $posOpen) . $replace . mb_substr($output, $posClose + 1);
    }

    public static function processEntities (string $_text, array $_message_raw): string
    {
        $preset = [
            'bold'      => '<b>%text</b>',
            'italic'    => '<i>%text</i>',
            'text_link' => '<a href="%url">%text</a>',
            'code'      => '<code>%text</code>',
            'pre'       => '<pre>%text</pre>',
        ];

        if (!isset ($_message_raw['entities']))
        {
            return $_text;
        }

        $iterationText = $_text;
        $globalDiff    = 0;
        foreach ($_message_raw['entities'] as $entity)
        {
            $type   = $entity['type'];
            $offset = $entity['offset'] + $globalDiff;
            $length = $entity['length'];

            $pBefore = \mb_substr ($iterationText, 0, $offset);
            $pText   = \mb_substr ($iterationText, $offset, $length);
            $pAfter  = \mb_substr ($iterationText, ($offset + $length));

            // Note: str_replace() works good with utf-8 in the last php versions.
            if (isset ($preset[$type]))
            {
                // Get pattern from the preset.
                $replacedContent = $preset[$type];

                // First, replace url, in that rare case, if in the text will be the %text macros.
                if (!empty ($entity['url']))
                {
                    $replacedContent = \str_replace ('%url', $entity['url'], $replacedContent);
                }

                // Replace main text.
                $replacedContent = \str_replace ('%text', $pText, $replacedContent);

                $newText       = $pBefore . $replacedContent . $pAfter;
                $globalDiff    += (\mb_strlen ($newText) - \mb_strlen ($iterationText));
                $iterationText = $newText;
            }
        }

        return $iterationText;
    }
    private static function mb_substr_replace2($string, $replacement, $start, $length = null, $encoding = null)
    {
        if ($encoding == null) $encoding = mb_internal_encoding();
        if ($length == null) {
            return mb_substr($string, 0, $start, $encoding) . $replacement;
        } else {
            if ($length < 0) $length = mb_strlen($string, $encoding) - $start + $length;
            return
                mb_substr($string, 0, $start, $encoding) .
                $replacement .
                mb_substr($string, $start + $length, mb_strlen($string, $encoding), $encoding);
        }
    }
}