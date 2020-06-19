<?php

namespace shopium\mod\telegram\components\Commands\AdminCommands;

use Longman\TelegramBot\Commands\AdminCommand;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use panix\engine\CMS;
use shopium\mod\telegram\components\Api;


/**
 * Admin "/debug" command
 */
class DebugCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'debug';

    /**
     * @var string
     */
    protected $description = 'Команда отладки, чтобы помочь найти проблемы';

    /**
     * @var string
     */
    protected $usage = '/debug';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    public function __construct(Api $telegram, Update $update = null)
    {

        if ($update->getCallbackQuery()) {
            $callbackQuery = $update->getCallbackQuery();
            $message = $callbackQuery->getMessage();
            $user = $callbackQuery->getFrom();

        } else {
            $message = $update->getMessage();
            $user = $message->getFrom();
        }
        // $chat = $message->getChat();
        // $chat_id = $chat->getId();
        $user_id = $user->getId();
        if (in_array($user_id, $telegram->defaultAdmins)) {
            $this->show_in_help = true;
            $this->enabled = true;
        } else {
            $this->show_in_help = false;
            $this->enabled = false;
        }
        parent::__construct($telegram, $update);
    }

    /**
     * Command execute method
     *
     * @return mixed
     * @throws TelegramException
     */
    public function execute()
    {
        $pdo = DB::getPdo();
        $message = $this->getMessage();
        $chat = $message->getChat();
        $text = strtolower($message->getText(true));

        $data = ['chat_id' => $chat->getId()];

        if ($text !== 'glasnost' && !$chat->isPrivateChat()) {
            $data['text'] = 'Only available in a private chat.';

            return Request::sendMessage($data);
        }

        $debug_info = [];

        $debug_info[] = sprintf('*Версия Бота:* `%s`', $this->telegram->getVersion());
        $debug_info[] = sprintf('*Download path:* `%s`', $this->telegram->getDownloadPath() ?: '`_Not set_`');
        $debug_info[] = sprintf('*Upload path:* `%s`', $this->telegram->getUploadPath() ?: '`_Not set_`');

        // Commands paths.
        $debug_info[] = '*Commands paths:*';
        $debug_info[] = sprintf(
            '```' . PHP_EOL . '%s```',
            json_encode($this->telegram->getCommandsPaths(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $php_bit = '';
        PHP_INT_SIZE === 4 && $php_bit = ' (32bit)';
        PHP_INT_SIZE === 8 && $php_bit = ' (64bit)';
        $debug_info[] = sprintf('*PHP версия:* `%1$s%2$s; %3$s; %4$s`', PHP_VERSION, $php_bit, PHP_SAPI, PHP_OS);
        $debug_info[] = sprintf('*Maximum PHP script execution time:* `%d сек`', ini_get('max_execution_time'));

        $mysql_version = $pdo ? $pdo->query('SELECT VERSION() AS version')->fetchColumn() : null;
        $debug_info[] = sprintf('*MySQL версия:* `%s`', $mysql_version ?: 'disabled');

        $debug_info[] = sprintf('*Операционная система:* `%s`', php_uname());

        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            $debug_info[] = sprintf('*Веб-сервер:* `%s`', $_SERVER['SERVER_SOFTWARE']);
        }
        if (function_exists('curl_init')) {
            $curlversion = curl_version();
            $debug_info[] = sprintf('*CURL версия:* `%1$s; %2$s`', $curlversion['version'], $curlversion['ssl_version']);
        }

        $webhook_info_title = '*Webhook информация:*';
        try {
            // Check if we're actually using the Webhook method.
            if (Request::getInput() === '') {
                $debug_info[] = $webhook_info_title . ' `Using getUpdates method, not Webhook.`';
            } else {
                $webhook_info_result = json_decode(Request::getWebhookInfo(), true)['result'];
                // Add a human-readable error date string if necessary.
                if (isset($webhook_info_result['last_error_date'])) {
                    $webhook_info_result['last_error_date_string'] = CMS::date($webhook_info_result['last_error_date']);
                }

                $webhook_info_result_str = json_encode(iconv('cp1251', 'utf-8', $webhook_info_result), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                $debug_info[] = $webhook_info_title;
                $debug_info[] = sprintf(
                    '```' . PHP_EOL . '%s```',
                    $webhook_info_result_str
                );
            }
        } catch (\Exception $e) {
            $debug_info[] = $webhook_info_title . sprintf(' `Failed to get webhook info! (%s)`', $e->getMessage());
        }

        $data['parse_mode'] = 'Markdown';
        $data['text'] = implode(PHP_EOL, $debug_info);

        return Request::sendMessage($data);
    }
}