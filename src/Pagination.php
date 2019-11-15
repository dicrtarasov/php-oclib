<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use dicr\helper\Html;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\data\DataProviderInterface;
use function is_array;
use const ENT_QUOTES;

/**
 * Виджет паринации Opencart.
 *
 * @property-read int $numPages кол-во сраниц
 *
 * @package dicr\oclib
 */
class Pagination extends Widget
{
    /** @var int общее количество записей */
    public $total;

    /** @var int номер страницы, начиная с 1 */
    public $page;

    /** @var int лимит записей на сраницу */
    public $limit;

    /** @var int количество ссылок на страницы */
    public $num_links;

    /** @var string шаблон URL сраницы, где номер сраницы помечен как "{page}" */
    public $url = '';

    /** @var string непонятно зачем */
    public $text;

    /** @var string|false символ первой сраницы */
    public $text_first = '|&lt;';

    /** @var string|false символ последней сраницы */
    public $text_last = '&gt;|';

    /** @var string|false символ следующей страницы */
    public $text_next = '&gt;';

    /** @var string|false символ предыдущей страницы */
    public $text_prev = '&lt;';

    /**
     * @var \yii\data\Pagination|null пейджер
     * Внимание ! Если пагинация берется у DataProvider, то для начала нужно инициировать его пейджер
     * методом вызова $provider->totalCount, иначе у пейджера будет total = 0
     */
    public $pager;

    /**
     * @var \yii\data\DataProviderInterface|null провайдер данных у которого возьмется пейджер и totalCount
     */
    public $provider;

    /**
     * Конструктор.
     *
     * @param array|\yii\data\Pagination|\yii\data\DataProviderInterface|null $params
     */
    public function __construct($params = null)
    {
        if (empty($params)) {
            $params = [];
        } elseif ($params instanceof DataProviderInterface) {
            $params = ['provider' => $params];
        } elseif ($params instanceof \yii\data\Pagination) {
            $params = ['pager' => $params];
        } elseif (! is_array($params)) {
            throw new InvalidArgumentException('params');
        }

        parent::__construct($params);
    }

    /**
     * Инициализация.
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (isset($this->provider)) {
            if (! ($this->provider instanceof DataProviderInterface)) {
                throw new InvalidConfigException('provider');
            }

            if (! isset($this->pager)) {
                $this->pager = $this->provider->getPagination();
            }

            if (empty($this->pager->totalCount)) {
                $this->pager->totalCount = (int)$this->provider->getTotalCount();
            }
        }

        if (isset($this->pager)) {
            if (! ($this->pager instanceof \yii\data\Pagination)) {
                throw new InvalidConfigException('pager');
            }

            $this->pager->pageParam = 'page';
            $this->pager->pageSizeParam = 'limit';

            if (empty($this->page)) {
                $this->page = $this->pager->page + 1;
            }

            if (empty($this->limit)) {
                $this->limit = $this->pager->pageSize;
            }

            if (! isset($this->total)) {
                $this->total = $this->pager->totalCount;
            }

            if (empty($this->url)) {
                $this->url = str_replace('999999999', '{page}', $this->pager->createUrl(999999998));
            }

        }

        Html::addCssClass($this->options, 'dicr-oclib-pagination pagination');
    }

    /**
     * @inheritDoc
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        if (empty($this->url)) {
            throw new InvalidConfigException('url');
        }

        $this->validate();

        $num_pages = $this->getNumPages();

        if ($num_pages < 2) {
            return;
        }

        echo Html::beginTag('ul', $this->options);

        if ($this->page > 1) {
            if (! empty($this->text_first)) {
                echo Html::tag('li', Html::a($this->text_first, $this->getUrl(1)));
            }

            if (! empty($this->text_prev)) {
                echo Html::tag('li', Html::a($this->text_prev, $this->getUrl($this->page - 1)));
            }
        }

        if ($num_pages > 1) {
            if ($num_pages <= $this->num_links) {
                $start = 1;
                $end = $num_pages;
            } else {
                $start = $this->page - (int)floor($this->num_links / 2);
                $end = $this->page + (int)floor($this->num_links / 2);

                if ($start < 1) {
                    $end += abs($start) + 1;
                    $start = 1;
                }

                if ($end > $num_pages) {
                    $start -= ($end - $num_pages);
                    $end = $num_pages;
                }
            }

            for ($i = $start; $i <= $end; $i ++) {
                if ($this->page === $i) {
                    echo Html::tag('li', Html::tag('span', $i), ['class' => 'active']);
                } else {
                    echo Html::tag('li', Html::a($i, $this->getUrl($i)));
                }
            }
        }

        if ($this->page < $num_pages) {
            if (! empty($this->text_next)) {
                echo Html::tag('li', Html::a($this->text_next, $this->getUrl($this->page + 1)));
            }

            if (! empty($this->text_last)) {
                echo Html::tag('li', Html::a($this->text_last, $this->getUrl($num_pages)));
            }
        }

        echo Html::endTag('li');
    }

    /**
     * Валидация параметров.
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function validate()
    {
        $this->page = (int)$this->page;
        if ($this->page < 0) {
            throw new InvalidConfigException('page');
        }

        if ($this->page < 1) {
            $this->page = 1;
        }

        $this->limit = (int)$this->limit;
        if ($this->limit < 0) {
            throw new InvalidConfigException('limit');
        }

        if ($this->limit < 1) {
            $this->limit = 20;
        }

        $this->total = (int)$this->total;
        if ($this->total < 0) {
            throw new InvalidConfigException('total');
        }

        $this->num_links = (int)$this->num_links;
        if ($this->num_links < 0) {
            throw new InvalidConfigException('num_links');
        }

        if ($this->num_links < 1) {
            $this->num_links = 8;
        }

        if (! empty($this->url)) {
            $this->url = str_replace('%7Bpage%7D', '{page}', $this->url);
            $this->url = html_entity_decode($this->url, ENT_QUOTES);
        }
    }

    /**
     * Возвращает кол-во сраниц.
     *
     * @return int
     */
    public function getNumPages()
    {
        return $this->limit > 0 ? (int)ceil($this->total / $this->limit) : 0;
    }

    /**
     * Возвращает ссылку на заданную страницу.
     *
     * @param int|null $page номер страницы. Если пустая, то текущая.
     * @return string
     */
    public function getUrl(int $page = null)
    {
        if (empty($page)) {
            $page = $this->page;
        }

        return rtrim($page > 1 ? str_replace('{page}', $page, $this->url) : str_replace('page={page}', '', $this->url),
            '?&');
    }

    /**
     * Рендер.
     *
     * @return string
     */
    public function render()
    {
        return (string)$this;
    }
}
