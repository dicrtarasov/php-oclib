<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 00:46:01
 */

declare(strict_types = 1);

namespace dicr\oclib;

use dicr\helper\Html;
use Yii;
use yii\base\BaseObject;
use yii\data\Sort;

/**
 * Страница сайта.
 *
 * @property string $title
 * @property string $description
 * @property string $keywords
 * @property string $ogImage
 * @property string $ogUrl
 * @property-read array $links
 * @property array $styles
 * @property-read array $scripts
 *
 * @package dicr\oclib
 * @noinspection PhpUnused
 */
class Document extends BaseObject
{
    /** @var string|null meta title */
    private $_title;

    /** @var string|null meta description */
    private $_description;

    /** @var string|null meta keywords */
    private $_keywords;

    /** @var string|null OG Image Url */
    private $_ogImage = '';

    /** @var string|null PG Page Url */
    private $_ogUrl;

    /** @var array */
    private $_links = [];

    /** @var array */
    private $_scripts = [];

    /** @var \yii\data\Sort|false */
    public $sort;

    /** @var \yii\data\Pagination|false */
    public $pager;

    /**
     * Инициализация.
     */
    public function init()
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
     * @return string|null
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Установить заголовок.
     *
     * @param string|null $title
     */
    public function setTitle(string $title = null)
    {
        $this->_title = isset($title) ?
            Html::decode(mb_strtoupper(mb_substr($title, 0, 1)) . mb_substr($title, 1)) : null;
    }

    /**
     * Возвращает meta description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * Устанавливает meta description.
     *
     * @param string|null $description
     */
    public function setDescription(string $description = null)
    {
        $this->_description = isset($description) ? Html::decode($description) : null;
    }

    /**
     * Возвращает meta keywords.
     *
     * @return string|null
     */
    public function getKeywords()
    {
        return $this->_keywords;
    }

    /**
     * Устанавливает meta keywords.
     *
     * @param string|null $keywords
     */
    public function setKeywords(string $keywords = null)
    {
        $this->_keywords = isset($keywords) ? Html::decode($keywords) : null;
    }

    /**
     * Возвращает OG Url.
     *
     * @return string|null
     */
    public function getOgUrl()
    {
        return $this->_ogUrl;
    }

    /**
     * Устанавливает OG Url
     *
     * @param $ogurl
     */
    public function setOgUrl(string $ogurl = null)
    {
        $this->_ogUrl = isset($ogurl) ? Html::decode($ogurl) : null;
    }

    /**
     * Возвращает OG Image.
     *
     * @return string|null
     */
    public function getOgImage()
    {
        return $this->_ogImage;
    }

    /**
     * Усанавливает OG Image.
     *
     * @param string|null $image
     */
    public function setOgImage(string $image = null)
    {
        $this->_ogImage = isset($image) ? Html::decode($image) : null;
    }

    /**
     * Добавляе ссылку link.
     *
     * @param string $href
     * @param string $rel
     */
    public function addLink(string $href, string $rel)
    {
        $this->_links[$href] = [
            'href' => $href,
            'rel' => $rel
        ];
    }

    /**
     * Возвращает ссылки link.
     *
     * @return mixed
     */
    public function getLinks()
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
    public function addStyle(string $href, string $rel = 'stylesheet', string $media = 'screen')
    {
        $this->_links[$href] = [
            'href' => $href,
            'rel' => $rel,
            'media' => $media
        ];
    }

    /**
     * Возвращает пусой массив, так как стили хранятся в links.
     *
     * @return array
     * @deprecated используйте getLinks()
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function getStyles()
    {
        return [];
    }

    /**
     * Добавляет ссылку на скрипт.
     *
     * @param string $href
     * @param string $position
     */
    public function addScript(string $href, string $position = 'header')
    {
        $this->_scripts[$href] = $position;
    }

    /**
     * Возвращает скрипты.
     *
     * @param string $position
     * @return array
     */
    public function getScripts(string $position = 'header')
    {
        if (empty($position)) {
            return $this->_scripts;
        }

        return array_values($this->_scripts[$position] ?? []);
    }

}
