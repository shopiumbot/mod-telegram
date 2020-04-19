<?php


namespace shopium\mod\telegram\components;

use Longman\TelegramBot\Entities\KeyboardButton;
use panix\engine\CMS;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;


class KeyboardMore extends Component
{
    /**
     * @var Pagination the pagination object that this pager is associated with.
     * You must set this property in order to make LinkPager work.
     */
    public $pagination;

    /**
     * @var string|bool the label for the "next" page button. Note that this will NOT be HTML-encoded.
     * If this property is false, the "next" page button will not be displayed.
     */
    public $nextPageLabel = '▶ еще';
    /**
     * @var string|bool the text label for the "last" page button. Note that this will NOT be HTML-encoded.
     * If it's specified as true, page number will be used as label.
     * Default is false that means the "last" page button will not be displayed.
     */
    public $lastPageLabel = '⏭ последняя';

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
        if ($pageCount < 2 && $this->hideOnSinglePage) {
            return 'empty';
        }

       // $this->buttons = [];
        $currentPage = $this->pagination->getPage();

        // next page
        if ($this->nextPageLabel !== false) {
            if (($page = $currentPage + 1) >= $pageCount - 1) {
                $page = $pageCount - 1;
            }
            $this->buttons[] = $this->renderPageButton($this->nextPageLabel, $page, $currentPage >= $pageCount - 1, false);
        }

        // last page
        $lastPageLabel = $this->lastPageLabel === true ? $pageCount : $this->lastPageLabel;
        if ($lastPageLabel !== false) {
            $this->buttons[] = $this->renderPageButton($lastPageLabel, $pageCount - 1, $currentPage >= $pageCount - 1, false);
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
        $callback = 'goPage_'.$page;
        if ($active) {
            $callback=time();
        }
        if ($disabled) {
            $callback=time();
        }

        // return Html::tag($linkWrapTag, Html::a($label, $this->pagination->createUrl($page), $linkOptions), $options);
        return new KeyboardButton(['text' => $label, 'callback_data' => $callback]);
    }
}
