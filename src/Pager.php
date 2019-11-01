<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnused */

declare(strict_types = 1);
namespace dicr\oclib;

use LogicException;
use Url;
use function in_array;

/**
 * Модель пейджера страниц.
 */
class Pager extends AbstractModel
{
    /** Направления сортировки */
    public const ORDER_ASC = 'ASC';

    public const ORDER_DESC = 'DESC';

    public const ORDERS = [
        self::ORDER_ASC,
        self::ORDER_DESC
    ];

    /** @var string тип сортировки */
    public $sort;

    /** @var string сортировка по-умолчанию */
    public $defaultSort;

    /** @var string порядок сортировки */
    public $order;

    /** @var string порядок по-умолчанию */
    public $defaultOrder;

    /** @var int общее кол-во элементов */
    public $total;

    /** @var int сраница 1... */
    public $page;

    /** @var int кол-во на странице */
    public $limit;

    /** @var int лимит по-умолчанию */
    public $defaultLimit;

    /** @var string маршрут сраницы */
    public $route;

    /** @var array дополнительные параметры Url::link */
    public $params;

    /**
     * Имя формы.
     *
     * @return string
     */
    public static function formName()
    {
        return '';
    }

    /**
     * Атрибуты для загрузки.
     *
     * @return array
     */
    public static function attributes()
    {
        $attrs = ['sort', 'order', 'page', 'limit'];
        return array_combine($attrs, $attrs);
    }

    /**
     * {@inheritDoc}
     * @see \dicr\oclib\AbstractModel::validate()
     */
    public function validate()
    {
        if (empty($this->sort) && ! empty($this->defaultSort)) {
            $this->sort = $this->defaultSort;
        }

        if (! empty($this->defaultOrder) && ! in_array($this->defaultOrder, self::ORDERS, false)) {
            throw new ValidateException($this, 'defaultOrder');
        }

        if (empty($this->order)) {
            if (! empty($this->defaultOrder)) {
                $this->order = $this->defaultOrder;
            }
        } elseif (! in_array($this->order, self::ORDERS)) {
            $this->order = null;
        }

        $this->page = (int)$this->page;
        if ($this->page < 1) {
            $this->page = 1;
        }

        $this->defaultLimit = (int)$this->defaultLimit;
        if ($this->defaultLimit < 0) {
            throw new ValidateException($this, 'defaultLimit');
        }

        $this->limit = (int)$this->limit;
        if ($this->limit < 1) {
            $this->limit = $this->defaultLimit ?: null;
        }

        if (isset($this->total)) {
            $this->total = (int)$this->total;
            if ($this->total < 0) {
                throw new ValidateException($this, 'total');
            }
        }

        $this->params = (array)($this->params ?: []);
    }

    /**
     * Возвращает количество сраниц.
     *
     * @return int
     */
    public function pagesCount()
    {
        if (! isset($this->total)) {
            throw new LogicException('total not set');
        }

        if (empty($this->limit)) {
            throw new LogicException('empty limit');
        }

        return (int)ceil($this->total / $this->limit);
    }

    /**
     * Возвращает канонические параметры страницы.
     *
     * @param array $params
     * @return array
     */
    public function buildParams(array $params = [])
    {
        // парамеры URL
        $params = array_merge($this->params, [
            'sort' => $this->sort ?: $this->defaultSort,
            'order' => $this->order ?: $this->defaultOrder,
            'page' => $this->page > 1 ? $this->page : null,
            'limit' => $this->limit ?: null
        ], $params);

        // удаляем значения по-умолчанию
        if (empty($params['sort']) || $params['sort'] === $this->defaultSort) {
            unset($params['sort']);
        }

        if (empty($params['order']) || $params['order'] === $this->defaultOrder) {
            unset($params['order']);
        }

        if (empty($params['page']) || ($params['page'] !== '{page}' && $params['page'] < 2)) {
            unset($params['page']);
        }

        if (empty($params['limit']) || (int)$params['limit'] === $this->defaultLimit) {
            unset($params['limit']);
        }

        return Filter::params($params);
    }

    /**
     * Сроит ссылку с данными пейджера и дополнительными параметрами.
     *
     * @param array $params
     * @return string
     * @throws \dicr\oclib\ValidateException
     */
    public function link(array $params = [])
    {
        // парамеры URL
        $params = $this->buildParams($params);

        if (empty($this->route)) {
            throw new ValidateException($this, 'route');
        }

        /** @noinspection PhpUndefinedMethodInspection */
        return BaseRegistry::app()->url->link($this->route, $params);
    }

    /**
     * Ссылка на предыдущую страницу.
     *
     * @return string
     * @throws \dicr\oclib\ValidateException
     */
    public function prev()
    {
        return $this->link(['page' => $this->page > 1 ? $this->page - 1 : 1]);
    }

    /**
     * Ссылка на следующую сраницу.
     *
     * @return string
     * @throws \dicr\oclib\ValidateException
     */
    public function next()
    {
        return $this->link(['page' => $this->page < $this->pagesCount() ? $this->page + 1 : $this->pagesCount()]);
    }

    /**
     * Ссылка на первую страницу.
     *
     * @return string
     * @throws \dicr\oclib\ValidateException
     */
    public function first()
    {
        return $this->link(['page' => 1]);
    }

    /**
     * Ссылка на последнюю страницую
     *
     * @return string
     * @throws \dicr\oclib\ValidateException
     */
    public function last()
    {
        return $this->link(['page' => $this->pagesCount()]);
    }
}
