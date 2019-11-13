<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\models;

use Debug;
use dicr\helper\ArrayHelper;
use dicr\validate\ValidateException;
use Filter;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use function array_key_exists;
use function count;
use function in_array;
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
 * @property-read \app\models\Manuf[] $selectedManufs выбранные в фильре производиели
 * @property-read \app\models\Attr[] $categAttrs характеристики товаров категории со значениями
 * @property-read \app\models\Attr[] $selectedAttrs выбранные в фильтре характеристики
 * @property-read array $pageParams канонические параметры сраницы
 * @property-read string $filterText текст выбранных параметров
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

    /** @var array фильтр характеристик id => [vals] */
    public $attrs;

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

            ['attrs', 'default'],
            [
                'attrs',
                function($attribute) {
                    if (empty($this->{$attribute})) {
                        $this->{$attribute} = null;
                        return;
                    }

                    $ret = [];
                    $attrs = $this->selectedAttrs;

                    // обходим все характеристики
                    foreach ((array)$this->{$attribute} as $id => $vals) {
                        //  проверяем id-характеристики и значения
                        $id = (int)$id;
                        if ($id < 1 || $vals === null || $vals === '' || $vals === []) {
                            continue;
                        }

                        $attr = $attrs[$id] ?? null;
                        if (empty($attr)) {
                            continue;
                        }

                        if ($attr->type === Attr::TYPE_FLAG) {
                            // если харакеристика типа флаг 0/1 (attr[5]=1)
                            if (!in_array((string)$vals, ['0', '1'], false)) {
                                $ret[$id] = $vals;
                            }
                        } elseif ($attr->type === Attr::TYPE_NUMBER) {
                            // если числовая характеристика с min/max (attr[5] = [min => 23, max => 123] )
                            $minmax = [];
                            foreach (['min', 'max'] as $key) {
                                if (isset($vals[$key]) && $vals[$key] !== '') {
                                    $minmax[$key] = (float)$vals[$key];
                                }
                            }

                            if (! empty($minmax)) {
                                $ret[$id] = $minmax;
                            }
                        } else {
                            // строковая характеристика (attr[5] = ['значение1', 'значение2', ...])
                            foreach ($vals as $i => $v) {
                                if ($v === null || $v === '') {
                                    unset($vals[$i]);
                                }
                            }

                            if (! empty($vals)) {
                                sort($vals);
                                $ret[$id] = array_unique($vals);
                            }
                        }
                    }

                    $this->{$attribute} = $ret ?: null;
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
     * Загрузка значений из данных.
     *
     * @param array $data
     * @param string|null $formName
     * @return bool
     */
    public function load($data, $formName = null)
    {
        // загружаем основные параметры фильтра
        $ret = parent::load($data, $formName);

        // дополнительно загружаем сокращенные варианты характеристик фильтра
        $this->manufacturer_id = (array)($this->manufacturer_id ?: []);
        $this->attrs = (array)($this->attrs ?: []);

        if ($formName !== null && $formName !== '') {
            $data = $data[$formName] ?? [];
        }

        // обходим все параметры
        foreach ($data as $param => $val) {
            $matches = null;
            if ($val === null || $val === '') {
                continue;
            }

            if (preg_match('~^m(\d+)$~', $param, $matches)) {
                // фильр производителей (m25=1)
                $id = (int)$matches[1];
                if ($id > 0 && (string)$val === '1') {
                    $this->manufacturer_id[] = $id;
                    $ret = true;
                }
            } elseif (preg_match('~^a(\d+)$~', $param, $matches)) {
                // характеристика типа флаг (attr25=1) или числовая без min/max (a235=5.13)
                $id = (int)$matches[1];
                if ($id > 0 && is_numeric($val)) {
                    $this->attrs[$id] = (float)$val;
                    $ret = true;
                }
            } elseif (preg_match('~^a(\d+)(min|max)$~', $param, $matches)) {
                // числовая харакеристика
                $id = (int)$matches[1];
                if ($id > 0 && is_numeric($val)) {
                    $this->attrs[$id][$matches[2]] = (float)$val;
                    $ret = true;
                }
            } elseif (preg_match('~^a(\d+)-(.+)$~', $param, $matches)) {
                // строковая характеристика (a25-значение=1)
                $id = (int)$matches[1];
                if ($id > 0 && in_array((string)$val, ['0', '1'], true)) {
                    $this->attrs[$id][] = $matches[2];
                    $ret = true;
                }
            }
        }

        return $ret;
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

        // фильтр характеристик
        if (! empty($this->attrs)) {
            // получаем все характеристики
            $attrs = $this->selectedAttrs;

            // обходим фильтр характеристик
            foreach ($this->attrs as $id => $vals) {
                // получаем текущую хараткристику
                $attr = $attrs[$id] ?? null;
                if (empty($attr)) {
                    continue;
                }

                $attrQuery = (new Query())->from(Prod::tableAttr() . ' pa')->where([
                    'pa.[[product_id]]' => new Expression('p.[[product_id]]'),
                    'pa.[[attribute_id]]' => $id
                ]);

                // проверяем тип
                if ($attr->type === Attr::TYPE_FLAG) {
                    $query->addSelect([
                        'a' . $id => $attrQuery->select(new Expression('cast(pa.[[text]] as unsigned)'))
                    ]);
                    $query->andHaving(['a' . $id => (int)(bool)$vals]);
                } elseif ($attr->type === Attr::TYPE_NUMBER) {
                    $attrQuery->select(new Expression('cast(pa.[[text]] as decimal(10,3))'));
                    if (is_numeric($vals)) {
                        $query->addSelect(['a' . $id => $attrQuery]);
                        $query->andHaving(['a' . $id => (float)$vals]);
                    } elseif (is_array($vals)) {
                        $min = isset($vals['min']) ? (float)$vals['min'] : null;
                        $max = isset($vals['max']) ? (float)$vals['max'] : null;
                        if (isset($min, $max)) {
                            [$min, $max] = [min($min, $max), max($min, $max)];
                            $query->addSelect(['a' . $id => $attrQuery]);
                            $query->andHaving(['between', 'a' . $id, $min, $max]);
                        } elseif (isset($min)) {
                            $query->addSelect(['a' . $id => $attrQuery]);
                            $query->andHaving(['>=', 'a' . $id, $min]);
                        } elseif (isset($max)) {
                            $query->addSelect(['a' . $id => $attrQuery]);
                            $query->andHaving(['<=', 'a' . $id, $max]);
                        }
                    }
                } else {
                    // сроковые значения
                    $vals = Filter::strings((array)$vals);

                    if (! empty($vals)) {
                        $query->addSelect(['a' . $id => $attrQuery->select('pa.[[text]]')]);
                        $query->andHaving(['a' . $id => $vals]);
                    }
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
            ]
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

    /** @var \app\models\Manuf выбранные производители */
    private $_selectedMaufs;

    /**
     * Выбранные произвдители.
     *
     * @return \app\models\Manuf[]
     */
    public function getSelectedManufs()
    {
        if (! isset($this->_selectedMaufs)) {
            $this->_selectedMaufs = ! empty($this->manuf) ? Manuf::find()->where([
                'manufacturer_id' => array_keys($this->manuf)
            ])->orderBy('name')->all() : [];
        }

        return $this->_selectedMaufs;
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
            $this->_categAttrs = ! empty($values) ? Attr::find()
                ->where(['attribute_id' => array_keys($values)])
                ->orderBy('name')
                ->indexBy('attribute_id')
                ->all() : [];

            // расставляем значения
            foreach ($this->_categAttrs as $id => $attr) {
                $vals = array_unique($values[$id]);
                sort($vals, $attr->type === Attr::TYPE_NUMBER ? SORT_NUMERIC : SORT_NATURAL);
                $attr->values = $vals;
            }
        }

        return $this->_categAttrs;
    }

    /** @var \app\models\Attr[] выбранные харакеристики */
    private $_selectedAttrs;

    /**
     * Возвращает выбранные харакеристики.
     *
     * @return \app\models\Attr[]
     */
    public function getSelectedAttrs()
    {
        if (! isset($this->_selectedAttrs)) {
            $this->_selectedAttrs = ! empty($this->attrs) ?
                Attr::find()->where(['attribute_id' => array_keys($this->attrs)])->orderBy('name')->indexBy('attribute_id')->all() : [];
        }

        return $this->_selectedAttrs;
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

        // обычные параметры
        foreach (['product_id', 'category_id', 'stock'] as $param) {
            if (! empty($this->{$param})) {
                $params[$param] = $this->{$param};
            }
        }

        // параметры производителя в укороченной форме
        foreach ((array)($this->manufacturer_id ?: []) as $id) {
            $params['m' . $id] = 1;
        }

        // характеристики в сокращенной форме
        $attrs = $this->selectedAttrs;
        foreach ($this->attrs ?: [] as $id => $vals) {
            $attr = $attrs[$id] ?? null;
            if (empty($attr)) {
                continue;
            }

            if ($attr->type === Attr::TYPE_FLAG) {
                $params['a' . $id] = (int)(bool)$vals;
            } elseif ($attr->type === Attr::TYPE_NUMBER) {
                if (is_numeric($vals)) {
                    $params['a' . $id] = (float)$vals;
                } elseif (is_array($vals)) {
                    if (isset($vals['min'])) {
                        $params['a' . $id . 'min'] = (float)$vals['min'];
                    }

                    if (isset($vals['max'])) {
                        $params['a' . $id . 'max'] = (float)$vals['max'];
                    }
                }
            } else {
                foreach ($vals as $val) {
                    $params['a' . $id . '-' . $val] = 1;
                }
            }
        }

        return $params;
    }

    /** @var string текстовое представление характеристик фильтра */
    private $_filterText;

    /**
     * Возвращает текстовое описание выбранных параметров.
     *
     * @return string
     */
    public function getFilterText()
    {
        if (! isset($this->_filterText)) {
            $params = [];

            if (! empty($this->selectedManufs)) {
                $params[] = 'Производитель ' . implode('/', ArrayHelper::getColumn($this->selectedManufs, 'name'));
            }

            foreach ($this->selectedAttrs as $attr) {
                $vals = $this->attrs[$attr->attribute_id];

                if ($attr->type === Attr::TYPE_FLAG) {
                    // значения 0/1
                    $params[] = $attr->name;
                } elseif ($attr->type === Attr::TYPE_NUMBER) {
                    if (is_numeric($vals)) {
                        // одно значение
                        $params[] = $attr->name . ' ' . (float)$vals;
                    } elseif (is_array($vals)) {
                        // значения [min => ..., max => ...]
                        $vals = array_unique(array_values($vals));
                        if (! empty($vals)) {
                            sort($vals);
                            $params[] = $attr->name . ' ' . implode('/', $vals);
                        }
                    }
                } else {
                    // значения [значение1, значение2, ...значениеN]
                    $vals = array_unique($vals);
                    if (! empty($vals)) {
                        sort($vals);
                        $params[] = $attr->name . ' ' . implode('/', $vals);
                    }
                }
            }

            $this->_filterText = implode(', ', $params);
        }

        return $this->_filterText;
    }
}
