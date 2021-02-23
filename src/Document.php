<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.02.21 13:45:41
 */

declare(strict_types = 1);

namespace dicr\oclib;

use dicr\helper\Html;
use dicr\helper\StringHelper;
use InvalidArgumentException;
use Yii;
use yii\base\BaseObject;
use yii\data\Pagination;
use yii\data\Sort;

use function array_filter;
use function array_keys;
use function array_values;

/**
 * Страница сайта.
 *
 * @property string $title title страницы
 * @property string $description meta description
 * @property string $keywords meta keywords
 * @property string $image ogImage
 * @property string|array|null $canonical
 * @property-read array $links
 * @property-read array $scripts
 * @property-read array $styles
 * @property string $h1
 * @property array $breadcrumbs
 * @property string $robots meta robots
 * @property Sort|false|null $sort
 * @property Pagination|false|null $pager
 */
class Document extends BaseObject
{
    /** @var ?string meta title */
    private $_title;

    /** @var ?string meta description */
    private $_description;

    /** @var ?string meta keywords */
    private $_keywords;

    /** @var ?string OG Image Url */
    private $_image;

    /** @var string|array|null канонический адрес страницы */
    private $_canonical;

    /** @var ?array */
    private $_links = [];

    /** @var ?array */
    private $_scripts = [];

    /** @var ?string */
    private $_h1;

    /** @var ?array */
    private $_breadcrumbs = [];

    /** @var ?string meta robots */
    private $_robots;

    /** @var Sort|false|null */
    private $_sort;

    /** @var Pagination|false|null */
    private $_pager;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->_sort === null) {
            $this->sort = new Sort(['route' => Yii::$app->requestedRoute]);
        }

        if ($this->pager === null) {
            $this->pager = new Pagination(['route' => Yii::$app->requestedRoute]);
        }
    }

    /**
     * Возвращает заголовок.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return (string)$this->_title;
    }

    /**
     * Установить заголовок.
     *
     * @param ?string $title
     */
    public function setTitle(?string $title): void
    {
        $title = (string)$title;
        if ($title !== '') {
            $title = StringHelper::mb_ucfirst(Html::decode($title));
        }

        $this->_title = $title;
    }

    /**
     * Возвращает meta description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return (string)$this->_description;
    }

    /**
     * Устанавливает meta description.
     *
     * @param ?string $description
     */
    public function setDescription(?string $description): void
    {
        $description = (string)$description;
        if ($description !== '') {
            $description = Html::decode($description);
        }

        $this->_description = $description;
    }

    /**
     * Возвращает meta keywords.
     *
     * @return string
     */
    public function getKeywords(): string
    {
        return (string)$this->_keywords;
    }

    /**
     * Устанавливает meta keywords.
     *
     * @param ?string $keywords
     */
    public function setKeywords(?string $keywords): void
    {
        $keywords = (string)$keywords;
        if ($keywords !== '') {
            $keywords = Html::decode($keywords);
        }

        $this->_keywords = $keywords;
    }

    /**
     * Возвращает OG Image.
     *
     * @return string
     */
    public function getImage(): string
    {
        return (string)$this->_image;
    }

    /**
     * Устанавливает OG Image.
     *
     * @param ?string $image
     */
    public function setImage(?string $image): void
    {
        $image = (string)$image;
        if ($image !== '') {
            $image = Html::decode($image);
        }

        $this->_image = $image;
    }

    /**
     * Канонический адрес страницы.
     *
     * @return array|string|null
     */
    public function getCanonical()
    {
        return $this->_canonical;
    }

    /**
     * Устанавливает канонический адрес страницы.
     *
     * @param string|array|null $canonical
     */
    public function setCanonical($canonical): void
    {
        $this->_canonical = $canonical;
    }

    /**
     * Добавляет ссылку link.
     *
     * @param string $href
     * @param string $rel
     */
    public function addLink(string $href, string $rel = 'stylesheet'): void
    {
        $this->_links[$href] = [
            'rel' => $rel,
            'href' => $href
        ];
    }

    /**
     * Возвращает ссылки link.
     *
     * @return array
     */
    public function getLinks(): array
    {
        return array_values($this->_links ?: []);
    }

    /**
     * Добавляет ссылку на css.
     *
     * @param string $href
     * @param string $rel
     * @param string $media
     */
    public function addStyle(string $href, string $rel = 'stylesheet', string $media = 'screen'): void
    {
        $this->_links[$href] = [
            'href' => $href,
            'rel' => $rel,
            'media' => $media
        ];
    }

    /**
     * Возвращает пустой массив, так как стили уже возвращены в links.
     *
     * @return array
     * @deprecated используйте getLinks()
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getStyles(): array
    {
        return [];
    }

    /**
     * Добавляет ссылку на скрипт.
     *
     * @param string $href
     * @param string $position
     */
    public function addScript(string $href, string $position = 'header'): void
    {
        $this->_scripts[$href] = $position;
    }

    /**
     * Возвращает скрипты.
     *
     * @param ?string $position
     * @return array
     */
    public function getScripts(?string $position = null): array
    {
        if (empty($position)) {
            return array_keys($this->_scripts);
        }

        return array_keys(array_filter(
            $this->_scripts,
            static fn(string $pos) => $pos === $position
        ));
    }

    /**
     * H1 страницы.
     *
     * @return string
     */
    public function getH1(): string
    {
        return (string)$this->_h1;
    }

    /**
     * Возвращает H1.
     *
     * @param string|null $h1
     */
    public function setH1(?string $h1): void
    {
        $h1 = (string)$h1;
        if ($h1 !== '') {
            $h1 = Html::decode($h1);
        }

        $this->_h1 = $h1;
    }

    /**
     * Возвращает breadcrumbs.
     *
     * @return array
     */
    public function getBreadcrumbs(): array
    {
        return $this->_breadcrumbs ?: [];
    }

    /**
     * Хлебные крошки.
     *
     * @param array|null $breadcrumbs
     */
    public function setBreadcrumbs(?array $breadcrumbs): void
    {
        $this->_breadcrumbs = $breadcrumbs;
    }

    /**
     * Возвращает meta robots
     *
     * @return string
     */
    public function getRobots(): string
    {
        return (string)$this->_robots;
    }

    /**
     * Устанавливает meta robots.
     *
     * @param string|null $robots
     */
    public function setRobots(?string $robots): void
    {
        $this->_robots = $robots;
    }

    /**
     * Сортировка.
     *
     * @return false|Sort|null
     */
    public function getSort()
    {
        return $this->_sort;
    }

    /**
     * Установить сортировку.
     *
     * @param Sort|false|null $sort
     */
    public function setSort($sort): void
    {
        if ($sort !== null && $sort !== false && ! $sort instanceof Sort) {
            throw new InvalidArgumentException('sort');
        }

        $this->_sort = $sort;
    }

    /**
     * Пагинация.
     *
     * @return Pagination|false|null
     */
    public function getPager()
    {
        return $this->_pager;
    }

    /**
     * Устанавливает пейджер.
     *
     * @param Pagination|false|null $pager
     */
    public function setPager($pager): void
    {
        if ($pager !== null && $pager !== false && ! $pager instanceof Pagination) {
            throw new InvalidArgumentException('pager');
        }

        $this->_pager = $pager;
    }
}
