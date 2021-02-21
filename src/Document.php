<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 21.02.21 10:10:22
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
 * @property ?string $title
 * @property ?string $description
 * @property ?string $keywords
 * @property ?string $ogImage
 * @property ?string $ogUrl
 * @property string|array|null $canonical
 * @property-read array $links
 * @property-read array $scripts
 * @property-read array $styles
 * @property ?string $h1
 * @property ?array $breadcrumbs
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
    private $_ogImage;

    /** @var ?string PG Page Url */
    private $_ogUrl;

    /** @var string|array|null канонический адрес страницы */
    private $_canonical;

    /** @var array */
    private $_links = [];

    /** @var array */
    private $_scripts = [];

    /** @var ?string */
    private $_h1;

    /** @var ?array */
    private $_breadcrumbs = [];

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
     * @return ?string
     */
    public function getTitle(): ?string
    {
        return $this->_title;
    }

    /**
     * Установить заголовок.
     *
     * @param ?string $title
     */
    public function setTitle(?string $title): void
    {
        if ($title !== null) {
            $title = StringHelper::mb_ucfirst(Html::decode($title));
        }

        $this->_title = $title;
    }

    /**
     * Возвращает meta description.
     *
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->_description;
    }

    /**
     * Устанавливает meta description.
     *
     * @param ?string $description
     */
    public function setDescription(?string $description): void
    {
        if ($description !== null) {
            $description = Html::decode($description);
        }

        $this->_description = $description;
    }

    /**
     * Возвращает meta keywords.
     *
     * @return ?string
     */
    public function getKeywords(): ?string
    {
        return $this->_keywords;
    }

    /**
     * Устанавливает meta keywords.
     *
     * @param ?string $keywords
     */
    public function setKeywords(?string $keywords): void
    {
        if ($keywords !== null) {
            $keywords = Html::decode($keywords);
        }

        $this->_keywords = $keywords;
    }

    /**
     * Возвращает OG Image.
     *
     * @return ?string
     */
    public function getOgImage(): ?string
    {
        return $this->_ogImage;
    }

    /**
     * Устанавливает OG Image.
     *
     * @param ?string $image
     */
    public function setOgImage(?string $image): void
    {
        if ($image !== null) {
            $image = Html::decode($image);
        }

        $this->_ogImage = $image;
    }

    /**
     * Возвращает OG Url.
     *
     * @return ?string
     */
    public function getOgUrl(): string
    {
        return $this->_ogUrl;
    }

    /**
     * Устанавливает OG Url
     *
     * @param ?string $ogUrl
     */
    public function setOgUrl(?string $ogUrl): void
    {
        if ($ogUrl !== null) {
            $ogUrl = Html::decode($ogUrl);
        }

        $this->_ogUrl = $ogUrl;
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
     * @return string|null
     */
    public function getH1(): ?string
    {
        return $this->_h1;
    }

    /**
     * Возвращает H1.
     *
     * @param string|null $h1
     */
    public function setH1(?string $h1): void
    {
        if ($h1 !== null) {
            $h1 = Html::decode($h1);
        }

        $this->_h1 = $h1;
    }

    /**
     * Возвращает breadcrumbs.
     *
     * @return array|null
     */
    public function getBreadcrumbs(): ?array
    {
        return $this->_breadcrumbs;
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
