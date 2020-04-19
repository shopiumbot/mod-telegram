<?php

namespace shopium\mod\telegram\commands\pager;

/**
 * Interface InlineKeyboardPaginator
 *
 * @package TelegramBot\InlineKeyboardPagination
 */
interface InlineKeyboardPaginator
{
    /**
     * InlineKeyboardPaginator constructor.
     *
     * @param array  $items
     * @param string $command
     * @param int    $selected_page
     * @param int    $items_per_page
     */
    public function __construct(array $items, string $command, int $selected_page, int $items_per_page);

    /**
     * Set the maximum number of keyboard buttons to show.
     *
     * @param int  $max_buttons
     * @param bool $force_button_count
     *
     * @return InlineKeyboardPagination
     */
    public function setMaxButtons(int $max_buttons = 5, bool $force_button_count = false): InlineKeyboardPagination;

    /**
     * Set command for this pagination.
     *
     * @param string $command
     *
     * @return InlineKeyboardPagination
     */
    public function setCommand(string $command = 'pagination'): InlineKeyboardPagination;

    /**
     * Set the selected page.
     *
     * @param int $selected_page
     *
     * @return InlineKeyboardPagination
     */
    public function setSelectedPage(int $selected_page): InlineKeyboardPagination;

    /**
     * Get the pagination data for the passed page.
     *
     * @param int $selected_page
     *
     * @return array
     */
    public function getPagination(int $selected_page = null): array;
}
