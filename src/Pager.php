<?php
namespace dicr\oclib;

/**
 * Модель пейджера страниц.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Pager extends Model
{
    /** Направления сортировки */
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';
    const ORDERS = [
        self::ORDER_ASC, self::ORDER_DESC
    ];

    /** @var string тип сортировки */
    public $sort;

    /** @var string порядок сортировки */
    public $order;

    /** @var int общее кол-во элементов */
    public $total = 0;

    /** @var int сраница 1... */
    public $page = 1;

    /** @var int кол-во на странице */
    public $limit = 100;

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
     * Правила валидации.
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'sort' => ['string'],
            'order' => ['set', 'vals' => self::ORDERS, 'def' => self::ORDER_ASC],
            'page' => ['int', 'def' => 1, 'min' => 1],
            'limit' => ['int', 'def' => 100, 'min' => 1],
            'total' => ['int', 'def' => 0, 'min' => 0],
            'route' => ['string', 'req']
        ];
    }

    /**
     * Возвращает количество сраниц.
     *
     * @throws \InvalidArgumentException
     * @return number
     */
    public function pagesCount()
    {
        $this->validate();
        return (int)ceil($this->total / $this->limit);
    }

    /**
     * Возвращае ссылку на заданную страницу.
     *
     * @param int $page
     * @return string
     */
    public function link(int $page)
    {
        if ($page < 1) {
            throw new \InvalidArgumentException('page');
        }

        $params = $this->params ?: [];

        if (!empty($this->sort)) {
            $params['sort'] = $this->sort;
        }

        if (!empty($this->order)) {
            $params['order'] = $this->order;
        }

        if (!empty($this->limit)) {
            $params['limit'] = $this->limit;
        }

        if ($page > 1) {
            $params['page'] = $page;
        }

        return Registry::app()->url->link($this->route, $params);
    }

    /**
     * Ссылка на первую страницу
     *
     * @return string
     */
    public function first()
    {
        return $this->link(1);
    }

    /**
     * Ссылка на последнюю страницу.
     *
     * @return string
     */
    public function last()
    {
        return $this->link($this->pagesCount());
    }
}