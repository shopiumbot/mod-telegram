<?php

namespace shopium\mod\telegram\commands\AdminCommands;


use core\modules\shop\models\Product;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use shopium\mod\telegram\components\UserCommand;


/**
 * Admin "/product" command
 */
class ProductCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'product';

    /**
     * @var string
     */
    protected $description = 'Информации о товаре';

    /**
     * @var string
     */
    protected $usage = '/product <id>';

    /**
     * @var string
     */
    protected $version = '1.0.0';
    protected $show_in_help = false;
    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
        $command = $message->getCommand();
        $text = trim($message->getText(true));


        $data = ['chat_id' => $chat_id];


        if ($command !== 'product') {
            $text = substr($command, 7);

        }


        if ($text === '') {
            $text = 'Укажите ID для поиска товара: /product <id>';


        } else {
            if (is_numeric($text)) {
                $product = Product::findOne($text);
                if ($product) {
                    return $this->telegram
                        ->setCommandConfig('productitem', [
                            'product' => $product,
                            'photo_index' => 0
                        ])
                        ->executeCommand('productitem');
                } else {
                    $text = 'Товар не найден';
                }
            } else {
                $text = 'ID должен быть числом';
            }

        }

        $data['text'] = $text;

        return Request::sendMessage($data);
    }
}
