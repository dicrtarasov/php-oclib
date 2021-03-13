<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 14.03.21 03:10:51
 */

declare(strict_types = 1);

namespace dicr\oclib;

use dicr\helper\Html;
use dicr\helper\StringHelper;
use InvalidArgumentException;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\data\Pagination;
use yii\data\Sort;
use yii\web\View;

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
    /** @var ?array */
    private $_links = [];

    /** @var ?array */
    private $_scripts = [];

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->sort === null) {
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
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getTitle(): string
    {
        return (string)Yii::$app->view->title;
    }

    /**
     * Установить заголовок.
     *
     * @param ?string $title
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setTitle(?string $title): void
    {
        $title = (string)$title;
        if ($title !== '') {
            $title = StringHelper::mb_ucfirst(Html::decode($title));
        }

        Yii::$app->view->title = $title;
    }

    /**
     * Возвращает meta description.
     *
     * @return string
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getDescription(): string
    {
        return (string)(Yii::$app->view->params['description'] ?? '');
    }

    /**
     * Устанавливает meta description.
     *
     * @param ?string $description
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setDescription(?string $description): void
    {
        $description = (string)$description;
        if ($description !== '') {
            $description = Html::decode($description);
        }

        Yii::$app->view->params['description'] = $description;
    }

    /**
     * Возвращает meta keywords.
     *
     * @return string
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getKeywords(): string
    {
        return (string)(Yii::$app->view->params['keywords'] ?? '');
    }

    /**
     * Устанавливает meta keywords.
     *
     * @param ?string $keywords
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setKeywords(?string $keywords): void
    {
        Yii::$app->view->params['keywords'] = $keywords;
    }

    /**
     * Возвращает OG Image.
     *
     * @return string
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getImage(): string
    {
        return (string)(Yii::$app->view->params['image'] ?? '');
    }

    /**
     * Устанавливает OG Image.
     *
     * @param ?string $image
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setImage(?string $image): void
    {
        $image = (string)$image;
        if ($image !== '') {
            $image = Html::decode($image);
        }

        Yii::$app->view->params['image'] = $image;
    }

    /**
     * Канонический адрес страницы.
     *
     * @return array|string|null
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getCanonical()
    {
        return Yii::$app->view->params['canonical'] ?? null;
    }

    /**
     * Устанавливает канонический адрес страницы.
     *
     * @param string|array|null $canonical
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setCanonical($canonical): void
    {
        Yii::$app->view->params['canonical'] = $canonical;
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

        Yii::$app->view->registerLinkTag([
            'rel' => $rel,
            'href' => $href
        ], $href);
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
     * @param ?string $media
     * @throws InvalidConfigException
     */
    public function addStyle(string $href, string $rel = 'stylesheet', ?string $media = null): void
    {
        $this->_links[$href] = [
            'href' => $href,
            'rel' => $rel,
            'media' => $media
        ];

        Yii::$app->view->registerCssFile($href, [
            'media' => $media
        ], $href);
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
     * @throws InvalidConfigException
     */
    public function addScript(string $href, string $position = View::POS_HEAD): void
    {
        $this->_scripts[$href] = $position;

        Yii::$app->view->registerJsFile($href, [
            'position' => $position
        ], $href);
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
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getH1(): string
    {
        return (string)(Yii::$app->view->params['h1'] ?? '');
    }

    /**
     * Возвращает H1.
     *
     * @param string|null $h1
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setH1(?string $h1): void
    {
        $h1 = (string)$h1;
        if ($h1 !== '') {
            $h1 = Html::decode($h1);
        }

        Yii::$app->view->params['h1'] = $h1;
    }

    /**
     * Возвращает breadcrumbs.
     *
     * @return array
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getBreadcrumbs(): array
    {
        return Yii::$app->view->params['breadcrumbs'] ?? [];
    }

    /**
     * Хлебные крошки.
     *
     * @param array|null $breadcrumbs
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setBreadcrumbs(?array $breadcrumbs): void
    {
        Yii::$app->view->params['breadcrumbs'] = $breadcrumbs;
    }

    /**
     * Возвращает meta robots
     *
     * @return string
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getRobots(): string
    {
        return (string)(Yii::$app->view->params['robots'] ?? '');
    }

    /**
     * Устанавливает meta robots.
     *
     * @param string|null $robots
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setRobots(?string $robots): void
    {
        Yii::$app->view->params['robots'] = $robots;
    }

    /**
     * Сортировка.
     *
     * @return false|Sort|null
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getSort()
    {
        return Yii::$app->view->params['sort'] ?? null;
    }

    /**
     * Установить сортировку.
     *
     * @param Sort|false|null $sort
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setSort($sort): void
    {
        if ($sort !== null && $sort !== false && ! $sort instanceof Sort) {
            throw new InvalidArgumentException('sort');
        }

        Yii::$app->view->params['sort'] = $sort;
    }

    /**
     * Пагинация.
     *
     * @return Pagination|false|null
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getPager()
    {
        return Yii::$app->view->params['pager'] ?? null;
    }

    /**
     * Устанавливает пейджер.
     *
     * @param Pagination|false|null $pager
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setPager($pager): void
    {
        if ($pager !== null && $pager !== false && ! $pager instanceof Pagination) {
            throw new InvalidArgumentException('pager');
        }

        Yii::$app->view->params['pager'] = $pager;
    }
}
