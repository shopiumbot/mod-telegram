<?php


namespace shopium\mod\telegram\components;

use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\KeyboardButton;
use panix\engine\CMS;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;


class InlineKeyboardMorePager extends Component
{
    /**
     * @var Pagination the pagination object that this pager is associated with.
     * You must set this property in order to make LinkPager work.
     */
    public $pagination;

    /**
     * @var int maximum number of page buttons that can be displayed. Defaults to 10.
     */
    public $maxButtonCount = 10;
    /**
     * @var string|bool the label for the "next" page button. Note that this will NOT be HTML-encoded.
     * If this property is false, the "next" page button will not be displayed.
     */
    public $nextPageLabel = '▶';
    /**
     * @var string|bool the text label for the "previous" page button. Note that this will NOT be HTML-encoded.
     * If this property is false, the "previous" page button will not be displayed.
     */
    public $prevPageLabel = '◀';
    /**
     * @var string|bool the text label for the "first" page button. Note that this will NOT be HTML-encoded.
     * If it's specified as true, page number will be used as label.
     * Default is false that means the "first" page button will not be displayed.
     */
    public $firstPageLabel = '⏮';
    /**
     * @var string|bool the text label for the "last" page button. Note that this will NOT be HTML-encoded.
     * If it's specified as true, page number will be used as label.
     * Default is false that means the "last" page button will not be displayed.
     */
    public $lastPageLabel = '⏭';

    /**
     * @var bool Hide widget when only one page exist.
     */
    public $hideOnSinglePage = true;
    /**
     * @var bool whether to render current page button as disabled.
     * @since 2.0.12
     */
    public $disableCurrentPageButton = false;
    public $buttons = [];
    public $internal = true;
    /**
     * Initializes the pager.
     */
    public function init()
    {
        parent::init();

        if ($this->pagination === null) {
            throw new InvalidConfigException('The "pagination" property must be set.');
        }
        return $this->renderPageButtons();
    }


    /**
     * Renders the page buttons.
     * @return string the rendering result
     */
    protected function renderPageButtons()
    {
        $pageCount = $this->pagination->getPageCount();

        $currentPage = $this->pagination->getPage();



        $begin = $currentPage * $this->pagination->pageSize;
       // $end = $begin + $count - 1;
       // if ($begin > $end) {
       //     $begin = $end;
       // }



        if ($begin >= $this->pagination->totalCount){
            $this->nextPageLabel=false;
        }



        // next page
        if ($this->nextPageLabel !== false) {
            $this->buttons[] = $this->renderPageButton($this->nextPageLabel, $currentPage + 1, false, false);
        }

        return $this->buttons;
    }

    /**
     * Renders a page button.
     * You may override this method to customize the generation of page buttons.
     * @param string $label the text label for the button
     * @param int $page the page number
     * @param string $class the CSS class for the page button.
     * @param bool $disabled whether this page button is disabled
     * @param bool $active whether this page button is active
     * @return string the rendering result
     */
    protected function renderPageButton($label, $page, $disabled, $active)
    {
        $callback = $this->generateCallbackData($page);
        if ($active) {
            $callback = time();
        }
        if ($disabled) {
            $callback = time();
            $label='Finish';
        }
echo $callback.PHP_EOL;
        // return Html::tag($linkWrapTag, Html::a($label, $this->pagination->createUrl($page), $linkOptions), $options);
        return new InlineKeyboardButton(['text' => $label, 'callback_data' => $callback]);
    }

    /**
     * @return array the begin and end pages that need to be displayed.
     */
    protected function getPageRange()
    {
        $currentPage = $this->pagination->getPage();
        $pageCount = $this->pagination->getPageCount();

        $beginPage = max(0, $currentPage - (int)($this->maxButtonCount / 2));
        if (($endPage = $beginPage + $this->maxButtonCount - 1) >= $pageCount) {
            $endPage = $pageCount - 1;
            $beginPage = max(0, $endPage - $this->maxButtonCount + 1);
        }

        return [$beginPage, $endPage];
    }

    public $callback_data = 'command={command}&page={page}';
    public $command = 'command';

    protected function generateCallbackData(int $page): string
    {
        return str_replace(['{command}', '{page}'], [$this->command, $page], $this->callback_data);
    }

    public static function getParametersFromCallbackData($data): array
    {
        parse_str($data, $params);

        return $params;
    }
}
