<?php

namespace shopium\mod\telegram\components\Commands\SystemCommands;

use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;
use shopium\mod\telegram\components\SystemCommand;

/**
 * Inline query command
 */
class InlinequeryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'inlinequery';

    /**
     * @var string
     */
    protected $description = 'Reply to inline query';

    /**
     * @var string
     */
    protected $version = '1.0.1';

    /**
     * Command execute method
     *
     * @return mixed
     */
    public function execute()
    {
        $inline_query = $this->getInlineQuery();
        $query        = $inline_query->getQuery();

        $data    = ['inline_query_id' => $inline_query->getId()];
        $results = [];

        if ($query !== '') {
            $articles = [
                [
                    'id'                    => '001',
                    'title'                 => 'https://core.telegram.org/bots/api#answerinlinequery',
                    'description'           => 'you enter: ' . $query,
                    'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $query]),
                ],
                [
                    'id'                    => '002',
                    'title'                 => 'https://core.telegram.org/bots/api#answerinlinequery',
                    'description'           => 'you enter: ' . $query,
                    'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $query]),
                ],
                [
                    'id'                    => '003',
                    'title'                 => 'https://core.telegram.org/bots/api#answerinlinequery',
                    'description'           => 'you enter: ' . $query,
                    'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $query]),
                ],
            ];

            foreach ($articles as $article) {
                $results[] = new InlineQueryResultArticle($article);
            }
        }

        return $this->getInlineQuery()->answer($results, $data);
    }
}
