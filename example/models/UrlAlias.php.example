<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace app\models;

use dicr\helper\Url;
use Yii;
use yii\base\InvalidArgumentException;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\Query;
use function array_diff;
use function array_map;
use function array_slice;
use function count;
use function implode;
use function is_array;
use function preg_match;
use function sprintf;
use function strlen;

/**
 * Алиас Url.
 *
 * @property-read int $url_alias_id
 * @property string $query маршрут либо параметры (в зависимости от типа алиаса)
 * @property string $keyword
 * @property string $meta_h1
 * @property string $meta_title
 * @property string $meta_desc
 * @property string $text1 html-текст1
 * @property string $text2 html-текст2
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
     * Таблица.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%url_alias}}';
    }

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        // нельзя делать проверку на unique, иначе не проходит upsert

        return [
            ['keyword', 'trim'],
            ['keyword', 'required'],
            ['keyword', 'string', 'max' => 144],

            ['query', 'required'],
            ['query', function ($attribute) {
                if (empty($this->{$attribute})) {
                    $this->addError($attribute, 'Требуется указать парамеры запроса');
                } elseif (is_array($this->{$attribute})) {
                    $this->{$attribute} = Url::buildQuery($this->{$attribute});
                }
            }],
            ['query', 'string', 'max' => 128],

            [['meta_title', 'meta_desc', 'meta_h1'], 'trim'],
            [['meta_title', 'meta_desc', 'meta_h1'], 'string', 'max' => 255],

            [['text1', 'text2'], 'trim'],
            [['text1', 'text2'], 'string', 'max' => 64000],
        ];
    }

    /**
     * Возвращает маршрут объекта по названию его id-параметра.
     *
     * @param string $param
     * @return string|null
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
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
     * query содержит либо route, либо параметры запроса (в зависимости от типа алиаса)
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
     * @noinspection PhpUnused
     */
    public function setQuery(array $query)
    {
        $this->_query = $query;
        $this->query = Url::buildQuery($query);
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
     * @noinspection PhpUnused
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
     * @return UrlAlias|null
     */
    public static function findRouteAlias(string $route)
    {
        return static::findOne(['query' => $route]);
    }

    /**
     * Устанавливает алиас для маршрута.
     *
     * @param string $keyword
     * @param string $route
     * @return bool
     * @throws Exception
     * @noinspection PhpUnused
     */
    public static function setRouteAlias(string $keyword, string $route)
    {
        if (empty($keyword)) {
            throw new InvalidArgumentException('keyword');
        }

        if (empty($route)) {
            throw new InvalidArgumentException('route');
        }

        return (new static(['keyword' => $keyword, 'query' => $route]))->upsert();
    }

    /**
     * Вставить/обновить существующий
     *
     *
     * @param bool $validate
     * @return bool
     * @throws Exception
     */
    public function upsert(bool $validate = true)
    {
        if ($validate && ! $this->validate()) {
            return false;
        }

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
     * Находит алиас для объекта и удаляет из параметров id-параметр объекта.
     *
     * @param string $route
     * @param array $params
     * @return UrlAlias|null
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
        $alias = static::findOne(['query' => $idParam . '=' . $idValue]);

        // удаляем из парамеров id-параметр
        if ($alias !== null) {
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
            if ($paramRoute['route'] === $route) {
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
     * @throws Exception
     * @noinspection PhpUnused
     */
    public static function setObjectAlias(string $keyword, string $route, int $id)
    {
        if (empty($keyword)) {
            throw new InvalidArgumentException('keyword');
        }

        if (empty($route)) {
            throw new InvalidArgumentException('route');
        }

        if ($id < 1) {
            throw new InvalidArgumentException('id');
        }

        // находим название id-парметра по маршруту
        $idParam = static::paramByRoute($route);
        if (empty($idParam)) {
            throw new InvalidArgumentException('route: ' . $route . ': неизвестный маршрут объекта');
        }

        return (new static([
            'keyword' => $keyword,
            'query' => $idParam . '=' . $id
        ]))->upsert();
    }

    /**
     * Находит алиас параметров и удаляет из парамеров парамеры найденного алиаса.
     * Подбирается алиас, у которого совпадает большее количество параметров в query.
     *
     * @param array $params
     * @return UrlAlias|null
     */
    public static function findMultiParamAlias(array &$params)
    {
        // получаем id алиаса из кэша
        $alias_id = Yii::$app->cache->getOrSet([__METHOD__, $params], static function () use ($params) {
            // фильтруем параметры, делая копию параметров и удаляя те, которые не могут участвовать в построении алиаса
            $flatParams = Url::normalizeQuery(Url::filterQuery(array_slice($params, 0)));
            unset($flatParams['sort'], $flatParams['order'], $flatParams['page'], $flatParams['limit'], $flatParams['route'], $flatParams['_route_']);
            if (empty($flatParams)) {
                return null;
            }

            $flatParams = Url::flatQuery($flatParams);

            /** @var self|null $alias алиас с максимальным кол-вом совпадений параметров */
            $alias = null;

            // если параметр всего один, то ускоряем поиск, не используя регулярные выражения
            if (count($flatParams) === 1) {
                $alias = static::find()->select(['url_alias_id', 'query'])->where(['query' => $flatParams[0]])->one();
            } else {
                $maxParamsCount = 0;

                // находим все алиасы, которые содержат хоть какие-то из парамеров
                $query = static::find()->select(['url_alias_id', 'query'])->where('[[query]] rlike :regex', [
                    ':regex' => '(^|&)(' . implode('|', array_map('\preg_quote', $flatParams)) . ')($|&)'
                ])->orderBy('length([[query]]) desc')->limit(1000);

                // обходим все и выбираем тот, у которого совпадает больше параметров
                foreach ($query->each() as $al) {
                    /** @var UrlAlias $al */

                    $alParams = Url::flatQuery($al->query);

                    // проверяем чтобы в найденном алиасе не было парамеров, которые отсутствуют в запрошенных
                    /** @noinspection NotOptimalIfConditionsInspection */
                    if (count($alParams) > count($flatParams) || ! empty(array_diff($alParams, $flatParams))) {
                        continue;
                    }

                    // если в данном алиасе больше совпадений чем прежде, то запоминаем его
                    if ($alias === null || count($alParams) > $maxParamsCount) {
                        $alias = $al;
                        $maxParamsCount = count($alParams);
                    }
                }
            }

            return isset($alias) ? $alias->url_alias_id : 0;
        }, null, new TagDependency(['tags' => self::class]));

        $alias = $alias_id > 0 ? self::findOne(['url_alias_id' => $alias_id]) : null;

        // если алиас найден, то удаляем его параметры и формируем новый query
        if (isset($alias)) {
            $params = Url::diffQuery($params, $alias->query);
        }

        return $alias;
    }

    /**
     * Находит все алиасы для заданных парамеров и удаляет из параметров парамеры найденных алиасов.
     * Поиск алиасов выполняется по максимальному количеству совпадающих парамеров.
     *
     * @param array $params
     * @return array
     */
    public static function findMultiParamAliases(array &$params)
    {
        $aliases = [];

        while (! empty($params)) {
            $alias = static::findMultiParamAlias($params);
            if ($alias === null) {
                break;
            }

            $aliases[] = $alias;
        }

        return $aliases;
    }

    /**
     * Находит алиас параметров и удаляет из парамеров парамеры найденного алиаса.
     * Подбирается алиас по одному парамеру.
     *
     * @param array $params
     * @return UrlAlias[]
     */
    public static function findSingleParamAliases(array &$params)
    {
        // делаем копию параметров и фильтруем, удаляя те, которые не могут участвовать в построении алиаса
        $flatParams = Url::normalizeQuery(Url::filterQuery(array_slice($params, 0)));

        unset($flatParams['sort'], $flatParams['order'], $flatParams['page'], $flatParams['limit'], $flatParams['route'], $flatParams['_route_']);
        if (empty($flatParams)) {
            return [];
        }

        /* @var string[] Для сравнения многомерных параметров переводим их в одномерный массив значений */
        $flatParams = Url::flatQuery($flatParams);

        /* @var UrlAlias[] $aliases находим все алиасы, которые содержат хоть какие-то из парамеров */
        $aliases = static::find()->where([
            'query' => $flatParams
        ])->orderBy('query')->all();

        // удаляем параметры алиасов из заданных параметров
        foreach ($aliases as $alias) {
            $params = Url::diffQuery($params, $alias->query);
        }

        return $aliases;
    }

    /**
     * Устанавливает алиас для параметров.
     *
     * @param string $keyword
     * @param array $params
     * @return bool
     * @throws Exception
     * @noinspection PhpUnused
     */
    public static function setParamsAlias(string $keyword, array $params)
    {
        if (empty($keyword)) {
            throw new InvalidArgumentException('keyword');
        }

        if (empty($params)) {
            throw new InvalidArgumentException('params');
        }

        return (new static([
            'keyword' => $keyword,
            'query' => Url::buildQuery(Url::normalizeQuery(Url::filterQuery($params)))
        ]))->upsert();
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

            $stat[$table] = static::deleteAll([
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
