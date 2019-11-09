<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\models;

use dicr\validate\ValidateException;
use Filter;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use function array_key_exists;
use function count;
use function is_array;
use const SORT_ASC;
use const SORT_DESC;
use const SORT_NATURAL;
use const SORT_NUMERIC;

/**
 * Модель фильтра товаров.
 *
 * @property-read \yii\db\ActiveQuery $query
 * @property-read \yii\data\ActiveDataProvider $provider
 * @property-read \app\models\Manuf[] $categManufs производители категории
 * @property-read \app\models\Attr[] $categAttrs характеристики товаров категории со значениями
 * @property-read array $pageParams канонические параметры сраницы
 */
class ProdFilter extends Model
{
    /** @var int отсутствует на складе */
    public const STOCK_ABSENT = 0;

    /** @var int наличие на складе */
    public const STOCK_STORE = 1;

    /** @var int наличие под заказ */
    public const STOCK_REQUEST = 2;

    /** @var int[] виды наличия */
    public const STOCKS = [
        self::STOCK_ABSENT,
        self::STOCK_STORE,
        self::STOCK_REQUEST
    ];

    /** @var string сортировка по-умолчанию */
    public const SORT_ORDER = 'sort_order';

    /** @var string сортировка по цене */
    public const SORT_PRICE = 'price';

    /** @var int|int[] id товаров */
    public $product_id;

    /** @var int|int[] категория */
    public $category_id;

    /** @var bool рекурсивно от категориии */
    public $recurse;

    /** @var int|int[] id производителей */
    public $manufacturer_id;

    /** @var array фильтр производителей */
    public $manuf;

    /** @var array фильтр характеристик */
    public $attr;

    /** @var bool статус товара */
    public $status;

    /** @var int наличие: 1 - на складе, 2 - под заказ */
    public $stock;

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['product_id', 'category_id', 'manufacturer_id'], 'default'],
            [
                ['product_id', 'category_id', 'manufacturer_id'],
                function($attribute) {
                    $this->{$attribute} = Filter::ids($this->{$attribute}) ?: null;
                    if (is_array($this->{$attribute}) && (count($this->{$attribute}) === 1)) {
                        $this->{$attribute} = reset($this->{$attribute});
                    }
                }
            ],

            ['recurse', 'default', 'value' => true],
            ['recurse', 'boolean'],
            ['recurse', 'filter', 'filter' => 'boolval'],

            ['manuf', 'default'],
            [
                'manuf',
                function($attribute) {
                    if (empty($this->{$attribute})) {
                        $vals = null;
                    } else {
                        $vals = [];
                        foreach ((array)$this->{$attribute} as $id => $val) {
                            $id = (int)$id;
                            if ($id > 0 && ! empty($val)) {
                                $vals[$id] = 1;
                            }
                        }
                    }

                    $this->{$attribute} = $vals ?: null;
                }
            ],

            ['attr', 'default'],
            [
                'attr',
                function($attribute) {
                    if (empty($this->{$attribute})) {
                        $vals = null;
                    } else {
                        $vals = [];
                        foreach ((array)$this->{$attribute} as $id => $val) {
                            $id = (int)$id;
                            if ($id < 1 || $val === null || $val === '' || $val === []) {
                                continue;
                            }

                            if (is_array($val) && (array_key_exists('min', $val) || array_key_exists('max', $val))) {
                                foreach (['min', 'max'] as $key) {
                                    if ($val[$key] === null || $val[$key] === '') {
                                        unset($val[$key]);
                                    }

                                    if (count($val) < 1) {
                                        continue;
                                    }
                                }
                            }

                            $vals[$id] = $val;
                        }
                    }

                    $this->{$attribute} = $vals ?: null;
                }
            ],

            ['status', 'default'],
            ['status', 'boolean'],
            ['status', 'filter', 'filter' => 'boolval', 'skipOnEmpty' => true],

            ['stock', 'default'],
            ['stock', 'in', 'range' => self::STOCKS],
            ['stock', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],
        ];
    }

    /**
     * Запрос товаров.
     *
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getQuery()
    {
        $query = Prod::find()->alias('p')->select('p.*')->innerJoin(Prod::tableCateg() . ' p2c',
                'p2c.[[product_id]]=p.[[product_id]]');

        if (! $this->validate()) {
            return $query->where('1=0');
        }

        // список id
        $query->andFilterWhere(['p.[[product_id]]' => $this->product_id]);

        // статус
        if (isset($this->status)) {
            $query->andWhere(['p.[[status]]' => (int)$this->status])->innerJoin(Categ::tableName() . ' c',
                    'c.[[category_id]]=p2c.[[category_id]]')->andWhere(['c.[[status]]' => (int)$this->status]);
        }

        // категория
        if (! empty($this->category_id)) {
            $query->andWhere([
                'p2c.[[category_id]]' => $this->recurse ? (new Query())->select('cp.[[category_id]]')
                    ->from(Categ::tablePath() . ' cp')
                    ->where(['cp.[[path_id]]' => $this->category_id]) : $this->category_id
            ]);
        }

        // производитель
        $query->andFilterWhere(['p.[[manufacturer_id]]' => $this->manufacturer_id]);

        // фильтр производителей
        if (! empty($this->manuf)) {
            $query->andWhere(['p.[[manufacturer_id]]' => array_keys($this->manuf)]);
        }

        // фильтр характеристик
        if (! empty($this->attr)) {
            // получаем все характеристики
            $attrs = Attr::find()->where(['attribute_id' => array_keys($this->attr)])->indexBy('attribute_id')->all();

            // обходим фильтр характеристик
            foreach ($this->attr as $id => $val) {
                // получаем текущую хараткристику
                $attr = $attrs[$id] ?? null;
                if ($attr === null) {
                    continue;
                }

                $attrQuery = (new Query())->select('pa.[[text]]')->from(Prod::tableAttr() . ' pa')->where([
                        'pa.[[product_id]]' => new Expression('p.[[product_id]]'),
                        'pa.[[attribute_id]]' => $id
                    ]);

                $valValid = false;

                // проверяем тип
                switch ($attr->type) {
                    case Attr::TYPE_FLAG:
                        $attrQuery->select('cast(pa.[[text]] as decimal(10,3))');
                        if ($val !== '') {
                            $attrQuery->select('cast(pa.[[text]] as unsigned)');
                            $valValid = true;
                        }
                        break;

                    case Attr::TYPE_NUMBER:
                        $attrQuery->select('cast(pa.[[text]] as decimal(10,3))');
                        $min = isset($val['min']) && $val['min'] !== '' ? (float)$val['min'] : null;
                        $max = isset($val['max']) && $val['max'] !== '' ? (float)$val['max'] : null;
                        if (isset($min, $max)) {
                            [$min, $max] = [min($min, $max), max($min, $max)];
                            $query->andHaving(['between', 'attr' . $id, $min, $max]);
                            $valValid = true;
                        } elseif (isset($min)) {
                            $query->andHaving(['>=', 'attr' . $id, $min]);
                            $valValid = true;
                        } elseif (isset($max)) {
                            $query->andHaving(['<=', 'attr' . $id, $max]);
                            $valValid = true;
                        }
                        break;

                    default:
                        $val = Filter::strings(array_keys((array)$val));
                        if (! empty($val)) {
                            $query->andHaving(['attr' . $id => $val]);
                            $valValid = true;
                        }
                }

                if ($valValid) {
                    $query->addSelect(['attr' . $id => $attrQuery]);
                }
            }
        }

        // наличие
        if ($this->stock === self::STOCK_STORE) {
            // @TODO не реализвано наличие "под заказ"
            $query->leftJoin(Prod::tableCity() . ' p2l', 'p2l.[[product_id]]=p.[[product_id]] and p2l.[[city]]=:city', [
                ':city' => City::current()->id
            ])->andWhere('p2l.[[city]] is not null');
        }

        return $query;
    }

    /**
     * Возвращае провайдер данных.
     *
     * @param array $config
     * @return \yii\data\ActiveDataProvider
     */
    public function getProvider(array $config = [])
    {
        return new ActiveDataProvider(array_merge([
            'query' => $this->query,
            'sort' => [
                'attributes' => [
                    self::SORT_ORDER => [
                        'asc' => [
                            'if(p.[[price]]>0,0,1)' => SORT_ASC,
                            'p.[[sort_order]]' => SORT_ASC
                        ],
                        'desc' => [
                            'if(p.[[price]]>0,0,1)' => SORT_DESC,
                            'p.[[sort_order]]' => SORT_DESC
                        ]
                    ],
                    self::SORT_PRICE => [
                        'asc' => [
                            'p.[[price]]' => SORT_ASC,
                            'p.[[sort_order]]' => SORT_ASC
                        ],
                        'desc' => [
                            'p.[[price]]' => SORT_DESC,
                            'p.[[sort_order]]' => SORT_DESC
                        ]
                    ]
                ],
                'defaultOrder' => [
                    self::SORT_ORDER => SORT_ASC
                ]
            ],
        ], $config));
    }

    /** @var \app\models\Manuf[] производители в категории товаров */
    private $_categManufs;

    /**
     * Возвращает производителей в категории товаров.
     *
     * @return \app\models\Manuf[]
     * @throws \dicr\validate\ValidateException
     */
    public function getCategManufs()
    {
        if (! isset($this->_categManufs)) {
            if (! $this->validate() || empty($this->category_id)) {
                /** @noinspection PhpParamsInspection */
                throw new ValidateException($this, 'category_id');
            }

            $this->_categManufs = (new ManufFilter([
                'category_id' => $this->category_id,
                'recurse' => $this->recurse
            ]))->query->cache(true, new TagDependency([
                'tags' => [Categ::class, Prod::class, Manuf::class]
            ]))->indexBy('manufacturer_id')->all();
        }

        return $this->_categManufs;
    }

    /** @var \app\models\Attr[] характеристики категории с наборами значений для фильтра */
    private $_categAttrs;

    /**
     * Возвращает список характеристик товаров категории и их хначения для фильра.
     *
     * @return \app\models\Attr[]
     * @throws \dicr\validate\ValidateException
     */
    public function getCategAttrs()
    {
        if (! isset($this->_categAttrs)) {
            if (! $this->validate() || empty($this->category_id)) {
                /** @noinspection PhpParamsInspection */
                throw new ValidateException($this, 'category_id');
            }

            // получаем карту id характеристик и их значений
            $query = (new Query())->select('pa.[[attribute_id]], pa.[[text]]')
                ->distinct(true)
                ->from(Prod::tableAttr() . ' pa')
                ->innerJoin(Prod::tableCateg() . ' p2c', 'p2c.[[product_id]]=pa.[[product_id]]')
                ->andWhere([
                    'p2c.[[category_id]]' => $this->recurse ? (new Query())->select('cp.[[category_id]]')
                        ->from(Categ::tablePath() . ' cp')
                        ->where(['cp.[[path_id]]' => $this->category_id]) : $this->category_id
                ])
                ->andWhere('pa.[[text]] != ""');

            if (isset($this->status)) {
                $query->innerJoin(Prod::tableName() . ' p', 'p.[[product_id]]=pa.[[product_id]]')
                    ->innerJoin(Categ::tableName() . ' c', 'c.[[category_id]]=p2c.[[category_id]]')
                    ->andWhere([
                        'p.[[status]]' => (int)$this->status,
                        'c.[[status]]' => (int)$this->status
                    ]);
            }

            $query->cache(true, new TagDependency([
                'tags' => [Attr::class, Categ::class]
            ]));

            /** @var array[] $values */
            $values = [];
            foreach ($query->all() as $row) {
                $values[(int)$row['attribute_id']][] = $row['text'];
            }

            // получаем все характеристики из выбранных id
            $this->_categAttrs = ! empty($values) ?
                Attr::find()->where(['attribute_id' => array_keys($values)])->indexBy('attribute_id')->all() : [];

            // расставляем значения
            foreach ($this->_categAttrs as $id => $attr) {
                $vals = array_unique($values[$id]);
                sort($vals, $attr->type === Attr::TYPE_NUMBER ? SORT_NUMERIC : SORT_NATURAL);
                $attr->values = $vals;
            }
        }

        return $this->_categAttrs;
    }

    /**
     * Возвращает параметры запроса.
     *
     * @return array
     */
    public function getPageParams()
    {
        $this->validate();

        $params = [];

        foreach (['product_id', 'category_id', 'manufacturer_id', 'manuf', 'attr', 'stock'] as $param) {
            if (! empty($this->{$param})) {
                $params[$param] = $this->{$param};
            }
        }

        return $params;
    }
}
