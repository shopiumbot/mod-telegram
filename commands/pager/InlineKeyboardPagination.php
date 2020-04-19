<?php

namespace shopium\mod\telegram\commands\pager;

use shopium\mod\telegram\commands\pager\Exceptions\InlineKeyboardPaginationException;

/**
 * Class InlineKeyboardPagination
 *
 * @package TelegramBot\InlineKeyboardPagination
 */
class InlineKeyboardPagination implements InlineKeyboardPaginator
{
    /**
     * @var integer
     */
    private $items_per_page;

    /**
     * @var integer
     */
    private $max_buttons = 5;

    /**
     * @var bool
     */
    private $force_button_count = false;

    /**
     * @var integer
     */
    private $selected_page;

    /**
     * @var array
     */
    private $items;

    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $callback_data_format = 'command={COMMAND}&oldPage={OLD_PAGE}&newPage={NEW_PAGE}';

    /**
     * @var array
     */
    private $labels = [
        'default'  => '%d',
        'first'    => '« %d',
        'previous' => '‹ %d',
        'current'  => '· %d ·',
        'next'     => '%d ›',
        'last'     => '%d »',
    ];

    /**
     * @inheritdoc
     * @throws InlineKeyboardPaginationException
     */
    public function setMaxButtons(int $max_buttons = 5, bool $force_button_count = false): InlineKeyboardPagination
    {
        if ($max_buttons < 5 || $max_buttons > 8) {
            throw new InlineKeyboardPaginationException('Invalid max buttons, must be between 5 and 8.');
        }
        $this->max_buttons        = $max_buttons;
        $this->force_button_count = $force_button_count;

        return $this;
    }

    /**
     * Get the current callback format.
     *
     * @return string
     */
    public function getCallbackDataFormat(): string
    {
        return $this->callback_data_format;
    }

    /**
     * Set the callback_data format.
     *
     * @param string $callback_data_format
     *
     * @return InlineKeyboardPagination
     */
    public function setCallbackDataFormat(string $callback_data_format): InlineKeyboardPagination
    {
        $this->callback_data_format = $callback_data_format;

        return $this;
    }

    /**
     * Return list of keyboard button labels.
     *
     * @return array
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Set the keyboard button labels.
     *
     * @param array $labels
     *
     * @return InlineKeyboardPagination
     */
    public function setLabels($labels): InlineKeyboardPagination
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCommand(string $command = 'pagination'): InlineKeyboardPagination
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws InlineKeyboardPaginationException
     */
    public function setSelectedPage(int $selected_page): InlineKeyboardPagination
    {
        $number_of_pages = $this->getNumberOfPages();
        if ($selected_page < 1 || $selected_page > $number_of_pages) {
            throw new InlineKeyboardPaginationException('Invalid selected page, must be between 1 and ' . $number_of_pages);
        }
        $this->selected_page = $selected_page;

        return $this;
    }

    /**
     * Get the number of items shown per page.
     *
     * @return int
     */
    public function getItemsPerPage(): int
    {
        return $this->items_per_page;
    }

    /**
     * Set how many items should be shown per page.
     *
     * @param int $items_per_page
     *
     * @return InlineKeyboardPagination
     * @throws InlineKeyboardPaginationException
     */
    public function setItemsPerPage($items_per_page): InlineKeyboardPagination
    {
        if ($items_per_page <= 0) {
            throw new InlineKeyboardPaginationException('Invalid number of items per page, must be at least 1');
        }
        $this->items_per_page = $items_per_page;

        return $this;
    }

    /**
     * Set the items for the pagination.
     *
     * @param array $items
     *
     * @return InlineKeyboardPagination
     * @throws InlineKeyboardPaginationException
     */
    public function setItems(array $items): InlineKeyboardPagination
    {
        if (empty($items)) {
            throw new InlineKeyboardPaginationException('Items list empty.');
        }
        $this->items = $items;

        return $this;
    }

    /**
     * Calculate and return the number of pages.
     *
     * @return int
     */
    public function getNumberOfPages(): int
    {
        return (int) ceil(count($this->items) / $this->items_per_page);
    }

    /**
     * TelegramBotPagination constructor.
     *
     * @inheritdoc
     * @throws InlineKeyboardPaginationException
     */
    public function __construct(array $items, string $command = 'pagination', int $selected_page = 1, int $items_per_page = 5)
    {
        $this->setCommand($command);
        $this->setItemsPerPage($items_per_page);
        $this->setItems($items);
        $this->setSelectedPage($selected_page);
    }

    /**
     * @inheritdoc
     * @throws InlineKeyboardPaginationException
     */
    public function getPagination(int $selected_page = null): array
    {
        if ($selected_page !== null) {
            $this->setSelectedPage($selected_page);
        }

        return [
            'items'    => $this->getPreparedItems(),
            'keyboard' => $this->generateKeyboard(),
        ];
    }

    /**
     * Generate the keyboard with the correctly labelled buttons.
     *
     * @return array
     */
    protected function generateKeyboard(): array
    {
        $buttons         = [];
        $number_of_pages = $this->getNumberOfPages();

        if ($number_of_pages > $this->max_buttons) {
            $buttons[1] = $this->generateButton(1);

            $range = $this->generateRange();
            for ($i = $range['from']; $i < $range['to']; $i++) {
                $buttons[$i] = $this->generateButton($i);
            }

            $buttons[$number_of_pages] = $this->generateButton($number_of_pages);
        } else {
            for ($i = 1; $i <= $number_of_pages; $i++) {
                $buttons[$i] = $this->generateButton($i);
            }
        }

        // Set the correct labels.
        foreach ($buttons as $page => &$button) {
            $in_first_block = $this->selected_page <= 3 && $page <= 3;
            $in_last_block  = $this->selected_page >= $number_of_pages - 2 && $page >= $number_of_pages - 2;

            $label_key = 'next';
            if ($page === $this->selected_page) {
                $label_key = 'current';
            } elseif ($in_first_block || $in_last_block) {
                $label_key = 'default';
            } elseif ($page === 1) {
                $label_key = 'first';
            } elseif ($page === $number_of_pages) {
                $label_key = 'last';
            } elseif ($page < $this->selected_page) {
                $label_key = 'previous';
            }

            $label = $this->labels[$label_key] ?? '';

            if ($label === '') {
                $button = null;
                continue;
            }

            $button['text'] = sprintf($label, $page);
        }

        return array_values(array_filter($buttons));
    }

    /**
     * Get the range of intermediate buttons for the keyboard.
     *
     * @return array
     */
    protected function generateRange(): array
    {
        $number_of_intermediate_buttons = $this->max_buttons - 2;
        $number_of_pages                = $this->getNumberOfPages();

        if ($this->selected_page === 1) {
            $from = 2;
            $to   = $this->max_buttons;
        } elseif ($this->selected_page === $number_of_pages) {
            $from = $number_of_pages - $number_of_intermediate_buttons;
            $to   = $number_of_pages;
        } else {
            if ($this->selected_page < 3) {
                $from = $this->selected_page;
                $to   = $this->selected_page + $number_of_intermediate_buttons;
            } elseif (($number_of_pages - $this->selected_page) < 3) {
                $from = $number_of_pages - $number_of_intermediate_buttons;
                $to   = $number_of_pages;
            } else {
                // @todo: Find a nicer solution for page 3
                if ($this->force_button_count) {
                    $from = $this->selected_page - floor($number_of_intermediate_buttons / 2);
                    $to   = $this->selected_page + ceil($number_of_intermediate_buttons / 2) + ($this->selected_page === 3 && $this->max_buttons > 5);
                } else {
                    $from = $this->selected_page - 1;
                    $to   = $this->selected_page + ($this->selected_page === 3 ? $number_of_intermediate_buttons - 1 : 2);
                }
            }
        }

        return compact('from', 'to');
    }

    /**
     * Generate the button for the passed page.
     *
     * @param int $page
     *
     * @return array
     */
    protected function generateButton(int $page): array
    {
        return [
            'text'          => (string) $page,
            'callback_data' => $this->generateCallbackData($page),
        ];
    }

    /**
     * Generate the callback data for the passed page.
     *
     * @param int $page
     *
     * @return string
     */
    protected function generateCallbackData(int $page): string
    {
        return str_replace(
            ['{COMMAND}', '{OLD_PAGE}', '{NEW_PAGE}'],
            [$this->command, $this->selected_page, $page],
            $this->callback_data_format
        );
    }

    /**
     * Get the prepared items for the selected page.
     *
     * @return array
     */
    protected function getPreparedItems(): array
    {
        return array_slice($this->items, $this->getOffset(), $this->items_per_page);
    }

    /**
     * Get the items offset for the selected page.
     *
     * @return int
     */
    protected function getOffset(): int
    {
        return $this->items_per_page * ($this->selected_page - 1);
    }

    /**
     * Get the parameters from the callback query.
     *
     * @todo Possibly make it work for custon formats too?
     *
     * @param string $data
     *
     * @return array
     */
    public static function getParametersFromCallbackData($data): array
    {
        parse_str($data, $params);

        return $params;
    }
}
