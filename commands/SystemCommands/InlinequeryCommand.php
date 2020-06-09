<?php

namespace shopium\mod\telegram\commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;

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
        //$inline_query = $this->getInlineQuery();
        //$user_id      = $inline_query->getFrom()->getId();
        //$query        = $inline_query->getQuery();

        return $this->getInlineQuery()->answer([]);
    }
}
