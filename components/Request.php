<?php


namespace shopium\mod\telegram\components;


class Request extends \Longman\TelegramBot\Request
{
    public static function getMyCommands()
    {


            $response     = self::send('getMyCommands');


        return $response;
    }
}