<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 19:39:05
 */

declare(strict_types = 1);

namespace dicr\oclib;

use dicr\helper\Html;
use dicr\helper\StringHelper;
use Yii;
use yii\base\BaseObject;
use yii\data\Sort;

/**
 * Страница сайта.
 *
 * @property ?string $title
 * @property ?string $description
 * @property ?string $keywords
 * @property ?string $ogImage
 * @property ?string $ogUrl
 * @property-read array $links
 * @property ?array $styles
 * @property-read array $scripts
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
    private $_ogImage = '';

    /** @var ?string PG Page Url */
    private $_ogUrl;

    /** @var array */
    private $_links = [];

    /** @var array */
    private $_scripts = [];

    /** @var Sort|false */
    public $sort;

    /** @var \yii\data\Pagination|false */
    public $pager;

    /**
     * @inheritDoc
     */
    public function init() : void
    {
        parent::init();

        if (! isset($this->sort)) {
            $this->sort = new Sort(['route' => Yii::$app->requestedRoute]);
        }

        if (! isset($this->pager)) {
            $this->pager = new \yii\data\Pagination(['route' => Yii::$app->requestedRoute]);
        }
    }

    /**
     * Возвращает заголовок.
     *
     * @return ?string
     */
    public function getTitle() : ?string
    {
        return $this->_title;
    }

    /**
     * Установить заголовок.
     *
     * @param ?string $title
     */
    public function setTitle(?string $title) : void
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
    public function getDescription() : ?string
    {
        return $this->_description;
    }

    /**
     * Устанавливает meta description.
     *
     * @param ?string $description
     */
    public function setDescription(?string $description) : void
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
    public function getKeywords() : ?string
    {
        return $this->_keywords;
    }

    /**
     * Устанавливает meta keywords.
     *
     * @param ?string $keywords
     */
    public function setKeywords(?string $keywords) : void
    {
        if ($keywords !== null) {
            $keywords = Html::decode($keywords);
        }

        $this->_keywords = $keywords;
    }

    /**
     * Возвращает OG Url.
     *
     * @return ?string
     */
    public function getOgUrl() : ?string
    {
        return $this->_ogUrl;
    }

    /**
     * Устанавливает OG Url
     *
     * @param ?string $ogUrl
     */
    public function setOgUrl(?string $ogUrl) : void
    {
        if ($ogUrl !== null) {
            $ogUrl = Html::decode($ogUrl);
        }

        $this->_ogUrl = $ogUrl;
    }

    /**
     * Возвращает OG Image.
     *
     * @return ?string
     */
    public function getOgImage() : ?string
    {
        return $this->_ogImage;
    }

    /**
     * Устанавливает OG Image.
     *
     * @param ?string $image
     */
    public function setOgImage(?string $image) : void
    {
        if ($image !== null) {
            $image = Html::decode($image);
        }

        $this->_ogImage = $image;
    }

    /**
     * Добавляет ссылку link.
     *
     * @param string $href
     * @param string $rel
     */
    public function addLink(string $href, string $rel) : void
    {
        $this->_links[$href] = [
            'href' => $href,
            'rel' => $rel
        ];
    }

    /**
     * Возвращает ссылки link.
     *
     * @return array
     */
    public function getLinks() : array
    {
        return $this->_links;
    }

    /**
     * Добавляет ссылку на css.
     *
     * @param string $href
     * @param string $rel
     * @param string $media
     */
    public function addStyle(string $href, string $rel = 'stylesheet', string $media = 'screen') : void
    {
        $this->_links[$href] = [
            'href' => $href,
            'rel' => $rel,
            'media' => $media
        ];
    }

    /**
     * Возвращает пустой массив, так как стили хранятся в links.
     *
     * @return array
     * @deprecated используйте getLinks()
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getStyles() : array
    {
        return [];
    }

    /**
     * Добавляет ссылку на скрипт.
     *
     * @param string $href
     * @param string $position
     */
    public function addScript(string $href, string $position = 'header') : void
    {
        $this->_scripts[$href] = $position;
    }

    /**
     * Возвращает скрипты.
     *
     * @param string $position
     * @return array
     */
    public function getScripts(string $position = 'header') : array
    {
        if (empty($position)) {
            return $this->_scripts;
        }

        return array_values($this->_scripts[$position] ?? []);
    }
}
