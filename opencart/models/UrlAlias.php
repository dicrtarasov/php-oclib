<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace app\models;

use dicr\helper\Url;
use yii\base\InvalidArgumentException;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\Query;
use function array_slice;
use function count;
use function is_array;

/**
 * Алиас Url.
 *
 * @property-read int $url_alias_id
 * @property string $query
 * @property string $keyword
 *
 * @property-read string $type тип алиаса (TYPE_*)
 * @property-read string $route маршрут алиаса (или пустая строка для алиаса парамеров)
 * @property-read array $params параметры URL алиаса (или пустой массив для алиаса маршрута)
 * @property-read array $link ссылка алиаса [0 => $route, ... $params]
 *
 * @package app\models
 */
class UrlAlias extends ActiveRecord
{
    /**
     * @var string тип алиаса - маршрут.
     * - $query указан маршрут, например: "account/login"
     * - $route = "account/login"
     * - $params = []
     */
    public const TYPE_ROUTE = 'route';

    /**
     * @var string тип алиаса - объект.
     * - $query указан id-параметр объекта, например "category_id=23&..."
     * - $route = "product/category"
     * - $params = ['category_id' => 23, ....]
     */
    public const TYPE_OBJECT = 'object';

    /** @var string тип алиаса - параметры.
     * - $query указаны произвольные параметры, например фильра: "attr=12&width=123&..."
     * - $route = '',
     * - $params = ['attr' => 12, 'width' => 123, ...]
     */
    public const TYPE_PARAMS = 'params';

    /** @var array типы */
    public const TYPES = [
        self::TYPE_ROUTE => 'маршрут',
        self::TYPE_OBJECT => 'объект',
        self::TYPE_PARAMS => 'параметры'
    ];

    /** @var int тип алиаса */
    private $_type;

    /** @var string маршрут */
    private $_route;

    /** @var array парамеры */
    private $_params;

    /** @var array значение query как массив */
    private $_query;

    /**
     * Возвращает маршрут объекта по названию его id-параметра.
     *
     * @param string $param
     * @return string|null
     */
    public static function routeByParam(string $param)
    {
        foreach (static::paramRoutes() as $paramRoute) {
            if ($paramRoute['param'] === $param) {
                return $paramRoute['route'];
            }
        }

        return null;
    }

    /**
     * Возвращает таблицу соответствия id-парамеров и роутов.
     *
     * @return array
     */
    public static function paramRoutes()
    {
        return [
            'oc_category' => [
                'param' => 'category_id',
                'route' => 'product/category'
            ],
            'oc_product' => [
                'param' => 'product_id',
                'route' => 'product/product'
            ],
            'oc_information' => [
                'param' => 'information_id',
                'route' => 'information/information'
            ],
            'oc_manufacturer' => [
                'param' => 'manufacturer_id',
                'route' => 'product/manufacturer/info'
            ],
            'oc_news' => [
                'param' => 'news_id',
                'route' => 'information/news/news'
            ],
            'oc_posts' => [
                'param' => 'posts_id',
                'route' => 'information/posts/posts'
            ]
        ];
    }

    /**
     * Возвращает название первого id-парамер из имеющихся в списке.
     *
     * @param array $params список параметров
     * @return string|null название первого найденного id-параметра
     */
    public static function idParam(array $params)
    {
        foreach (static::paramRoutes() as $paramRoute) {
            $param = $paramRoute['param'];
            if (isset($params[$param])) {
                return $param;
            }
        }

        return null;
    }

    /**
     * Возвращает тип алиаса.
     *
     * @return int TYPE_*
     */
    public function getType()
    {
        if (! isset($this->_type)) {
            $this->parseQueryType();
        }

        return $this->_type;
    }

    /**
     * Установить значение типа.
     *
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->_type = $type;
    }

    /**
     * Парсит значение query и определяе тип алиаса, маршрут и параметры.
     */
    protected function parseQueryType()
    {
        // парсим query
        $params = $this->getQuery();

        /** если в query указан маршрут, тогда после parse_str он будет как один ключ в $params с пустым значением,
         * например $params['account/login'] = '' */
        if (preg_match('~([\w_-]+/)+[\w-_]+$~um', $this->query)) {
            $this->_type = self::TYPE_ROUTE;
            $this->_route = $this->query;
            $this->_params = [];
        } else {
            // пытаемся определить алиас обьекта по наличию id-параметра
            $route = static::routeByParams($params);
            if (! empty($route)) {
                $this->_type = self::TYPE_OBJECT;
                $this->_route = $route;
                $this->_params = $params;
            } else {
                // считаем алиасом параметров
                $this->_type = self::TYPE_PARAMS;
                $this->_route = '';
                $this->_params = $params;
            }
        }
    }

    /**
     * Возвращает query как массив.
     *
     * @return array
     */
    public function getQuery()
    {
        if (! isset($this->_query)) {
            $this->_query = Url::parseQuery($this->query);
        }

        return $this->_query;
    }

    /**
     * Устанавливает query как массив.
     *
     * @param array $query
     */
    public function setQuery(array $query)
    {
        $this->_query = $query;
        $this->query = http_build_query($query);
    }

    /**
     * Определяет маршрут по наличию id-парамера.
     * Например, если присутствует "category_id", то маршрут "product/category".
     *
     * @param array $params параметры URL.
     * @return string|null маршрут
     */
    public static function routeByParams(array $params)
    {
        if (! empty($params['route'])) {
            return $params['route'];
        }

        foreach (static::paramRoutes() as $paramRoute) {
            $param = $paramRoute['param'];
            if (isset($params[$param])) {
                return $paramRoute['route'];
            }
        }

        return null;
    }

    /**
     * Возвращает ссылку для алисасов маршрута и объекта.
     * Для алиаса параметров вернет null.
     *
     * @return array|null ссылка в формате [0 => route, ...params] или null если эо алиас парамеров.
     */
    public function getLink()
    {
        $route = $this->getRoute();
        if (empty($route)) {
            return null;
        }

        $link = $this->getParams();
        $link[0] = $route;
        return $link;
    }

    /**
     * Возвращает маршрут алиаса. Для алиаса параметров маршрут - пустая срока ''.
     *
     * @return string
     */
    public function getRoute()
    {
        if (! isset($this->_route)) {
            $this->parseQueryType();
        }

        return $this->_route;
    }

    /**
     * Возвращает парамеры URL алиаса. Для алиаса маршрута парамеры пустые - []
     *
     * @return array
     */
    public function getParams()
    {
        if (! isset($this->_params)) {
            $this->parseQueryType();
        }

        return $this->_params;
    }

    /**
     * Возвращает алиас для роута.
     *
     * @param string $route
     * @return \app\models\UrlAlias|null
     */
    public static function findRouteAlias(string $route)
    {
        return self::findOne(['query' => $route]);
    }

    /**
     * Устанавливает алиас для маршрута.
     *
     * @param string $keyword
     * @param string $route
     * @return bool
     * @throws \yii\db\Exception
     */
    public static function setRouteAlias(string $keyword, string $route)
    {
        if (empty($keyword)) {
            throw new InvalidArgumentException('keyword');
        }

        if (empty($route)) {
            throw new InvalidArgumentException('route');
        }

        return (new self(['keyword' => $keyword, 'query' => $route]))->upsert(true);
    }

    /**
     * Вставить/обновить существующий
     *
     *
     * @param bool $validate
     * @return bool
     * @throws \yii\db\Exception
     */
    public function upsert(bool $validate = true)
    {
        if ($validate && ! $this->validate()) {
            return false;
        }

        /** @noinspection MissedFieldInspection */
        static::getDb()->createCommand()->upsert(static::tableName(), [
            'keyword' => $this->keyword,
            'query' => $this->query
        ], [
            'keyword' => $this->keyword
        ])->execute();

        // чтобы пройти валидацию, а также после сохранения, сбрасываем флаг новый
        $this->setIsNewRecord(false);

        return true;
    }

    /**
     * Таблица.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{oc_url_alias}}';
    }

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        // нельзя делать проверку на uique, иначе не проходит upsert

        return [
            ['query', 'required'],
            [
                'query',
                function($attribute) {
                    if (empty($this->{$attribute})) {
                        $this->addError($attribute, 'Требуется указать парамеры запроса');
                    } elseif (is_array($this->{$attribute})) {
                        $this->{$attribute} = http_build_query($this->{$attribute});
                    }
                }
            ],
            ['query', 'string', 'max' => 512],

            ['keyword', 'trim'],
            ['keyword', 'required'],
            ['keyword', 'string', 'max' => 144],
        ];
    }

    /**
     * Находит алиас для объекта и удаляет из параметров id-параметр объекта.
     *
     * @param string $route
     * @param array $params
     * @return \app\models\UrlAlias|null
     */
    public static function findObjectAlias(string $route, array &$params)
    {
        // определяем название id-параметра
        $idParam = static::paramByRoute($route);
        if (empty($idParam)) {
            return null;
        }

        // определяем значение id-парамера
        $idValue = $params[$idParam] ?? null;
        if (empty($idValue)) {
            return null;
        }

        // находим алиас для объекта
        $alias = self::findOne(['query' => $idParam . '=' . $idValue]);

        // удаляем из парамеров id-параметр
        if (! empty($alias)) {
            unset($params[$idParam]);
        }

        return $alias;
    }

    /**
     * Возвращает название id-парамера объекта по его маршруту.
     *
     * @param string $route
     * @return string|null
     */
    public static function paramByRoute(string $route)
    {
        foreach (static::paramRoutes() as $paramRoute) {
            if ($paramRoute['route'] === $paramRoute) {
                return $paramRoute['param'];
            }
        }

        return null;
    }

    /**
     * Устанавливает алиас для объекта.
     *
     * @param string $keyword
     * @param string $route маршрут объекта
     * @param int $id ID объекта
     * @return bool
     * @throws \yii\db\Exception
     */
    public static function setObjectAlias(string $keyword, string $route, int $id)
    {
        if (empty($keyword)) {
            throw new InvalidArgumentException('keyword');
        }

        if (empty($route)) {
            throw new InvalidArgumentException('route');
        }

        if (empty($id)) {
            throw new InvalidArgumentException('id');
        }

        // находим название id-парметра по маршруту
        $idParam = self::paramByRoute($route);
        if (empty($idParam)) {
            throw new InvalidArgumentException('route: ' . $route . ': неизвестный маршрут объекта');
        }

        return (new self([
            'keyword' => $keyword,
            'query' => $idParam . '=' . $id
        ]))->upsert(true);
    }

    /**
     * Находит алиас параметров и удаляет из парамеров парамеры найденного алиаса.
     * Подбирается алиас, у которого совпадает большее количество параметров в query.
     *
     * @param array $params
     * @return \app\models\UrlAlias|null
     */
    public static function findParamAlias(array &$params)
    {
        // фильтруем параметры, удаляя те, которые не могут участвовать в посроении алиаса
        $flatParams = Url::normalizeQuery(Url::filterQuery(array_slice($params, 0)));
        unset($flatParams['sort'], $flatParams['order'], $flatParams['page'], $flatParams['limit'], $flatParams['route'], $flatParams['_route_']);
        if (empty($flatParams)) {
            return null;
        }

        // Для сравнения многомерных параметров переводим их в одномерный массив значений
        $flatParams = Url::flatQuery($flatParams);

        /** @var self|null $maxAlias алиас с максимальным кол-вом совпадений параметров */
        $maxAlias = null;

        /** @var array $maxParams парамеры найденного алиаса в одномерном виде */
        $maxParams = null;

        // если параметр всего один, то ускоряем поиск, не используя регулярные выражения
        if (count($flatParams) === 1) {
            $maxAlias = self::findOne(['query' => $flatParams[0]]);
        } else {
            // находим все алиасы, которые содержат хоть какие-то из парамеров
            $query = self::find()->where('[[query]] rlike :regex', [
                ':regex' => '[[:<:]](' . implode('|', array_map('preg_quote', $flatParams)) . ')[[:>:]]'
            ])->orderBy('keyword')->cache(true, new TagDependency([
                'tags' => [self::class]
            ]));

            // обходим все и выбираем тот, у которого совпадает больше параметров
            foreach ($query->each() as $alias) {
                $aliasParams = Url::flatQuery($alias->query);

                // проверяем чтобы в найденном алиасе не было парамеров, которые отсутствуют в запрошенных
                if (count($aliasParams) > count($flatParams) || ! empty(array_diff($aliasParams, $flatParams))) {
                    continue;
                }

                // если в данном алиасе больше совпадений чем прежде, то запоминаем его
                if (! isset($maxParams) || count($aliasParams) > count($maxParams)) {
                    $maxAlias = $alias;
                    $maxParams = $aliasParams;
                }
            }
        }

        // если алиас найден, то удаляем его параметры и формируем новый query
        if (! empty($maxAlias)) {
            $params = Url::diffQuery($params, $maxAlias->query);
        }

        return $maxAlias;
    }

    /**
     * Устанавливает алиас для параметров.
     *
     * @param string $keyword
     * @param array $params
     * @return bool
     * @throws \yii\db\Exception
     */
    public static function setParamsAlias(string $keyword, array $params)
    {
        if (empty($keyword)) {
            throw new InvalidArgumentException('keyword');
        }

        if (empty($params)) {
            throw new InvalidArgumentException('params');
        }

        return (new self([
            'keyword' => $keyword,
            'query' => Url::buildQuery(Url::normalizeQuery(Url::filterQuery($params)))
        ]))->upsert(true);
    }

    /**
     * Удаление алиасов, id-парамеры которых ссылаются на несуществующие объекы.
     *
     * @return array [table => deleted_count], ключ - таблица объека, значение - количество удаленных алиасов
     */
    public static function cleanUnused()
    {
        $stat = [];

        foreach (static::paramRoutes() as $table => $paramRoute) {
            $param = $paramRoute['param'];

            $stat[$table] = UrlAlias::deleteAll([
                'and',
                '[[query]] like "' . $param . '=%"',
                [
                    'not exists',
                    (new Query())->from($table)->where(sprintf('[[%s]]=cast(substring([[query]], %d) as UNSIGNED)',
                        $param, strlen($param) + 2))
                ]
            ]);
        }

        return $stat;
    }
}
