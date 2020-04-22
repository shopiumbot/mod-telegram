[![Latest Stable Version](https://poser.pugx.org/shopium/mod-telegram/v/stable)](https://packagist.org/packages/shopium/mod-telegram)
[![Total Downloads](https://poser.pugx.org/shopium/mod-telegram/downloads)](https://packagist.org/packages/shopium/mod-telegram)
[![License](https://poser.pugx.org/shopium/mod-telegram/license)](https://packagist.org/packages/shopium/mod-telegram)
[![Daily Downloads](https://poser.pugx.org/shopium/mod-telegram/d/daily)](https://packagist.org/packages/shopium/mod-telegram)
[![Monthly Downloads](https://poser.pugx.org/shopium/mod-telegram/d/monthly)](https://packagist.org/packages/shopium/mod-telegram)

**Support chat for site based on Telegram bot**

The Bot logic based on [akalongman/php-telegram-bot](https://github.com/akalongman/php-telegram-bot), so you can read Instructions by longman how to register Telegram Bot and etc.

***Now only telegram webhook api support. You need SSL cert! Doesn't work on http!*** 

**Installation**
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Run


    composer require shopium/mod-telegram

 
 add to your config:
  
     'modules' => [
	     //...
        'telegram' => [
            'class' => 'shopium\mod\telegram\Module',
            'PASSPHRASE' => 'passphrase for login',
            // 'db' => 'db2', //db file name from config dir
	        // 'userCommandsPath' => '@app/modules/telegram/UserCommands',
	        // 'timeBeforeResetChatHandler' => 60
        ]
	    //more...
     ]
run migrations:

    php yii migrate --migrationPath=@vendor/shopium/mod-telegram/migrations #that add 4 tables in your DB

or add to your config file
```
'controllerMap' => [
    ...
    'migrate' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationNamespaces' => [
            'shopium\mod\telegram\migrations',
        ],
    ],
    ...
],
```
and run

```
php yii migrate/up
```

go to https://yourhost.com/telegram/default/set-webhook (if not prettyUrl https://yourhost.com/index.php?r=telegram/default/set-webhook)

Now you can place where you want

    echo \shopium\mod\telegram\TelegramWidget::widget(); //that add chat button in the page


If you want to limit the storage period of messages history, add to you crontab:

    #leave 5 days (if empty - default = 7)
    php yii telegram/messages/clean 5

Also you can use custom commands. To do this, you can copy UserCommands dir from /vendor/panix/mod-telegram/Commands and add path to this in config, for example:

    'userCommandsPath' => '@app/modules/telegram/UserCommands'
    

**timeBeforeResetChatHandler** - the number of minutes before chat handler will be killed (if he forgot do /leavedialog). Never kill if 0 or not setted.
