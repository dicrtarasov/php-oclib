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
use Yii;
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

                    // обходим все характеристики
                    foreach ((array)$this->{$attribute} as $id => $vals) {
                        //  проверяем id-характеристики и значения
                        $id = (int)$id;
                        if ($id < 1 || $vals === null || $vals === '' || $vals === []) {
                            continue;
                        }

                        // переводим значение в массив
                        $vals = (array)$vals;

                        // значение типа min/max
                        if (array_key_exists('min', $vals) || array_key_exists('max', $vals)) {
                            foreach (['min', 'max'] as $field) {
                                if (isset($vals[$field]) && $vals[$field] !== '') {
                                    $ret[$id][$field] = $vals[$field];
                                }
                            }
                        } else {
                            // обычный массив значений
                            foreach ($vals as $i => $v) {
                                if (!isset($v) || $v === '') {
                                    unset($vals[$i]);
                                }
                            }

                            if (!empty($vals)) {
                                sort($vals);
                                $vals = array_unique($vals);
                                $ret[$id] = count($vals) > 1 ? $vals : reset($vals);
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
            if ($val === null || $val === '' || $val === []) {
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
                // фильтр характеристик
                $id = (int)$matches[1];
                if ($id > 0) {
                    $this->attrs[$id] = $val;
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
                if (empty($attr) || $vals === null || $vals === '' || $vals === []) {
                    continue;
                }

                $vals = (array)$vals;

                if ($attr->type === Attr::TYPE_FLAG) {
                    $cast = 'cast(pa.[[text]] as unsigned)';
                } elseif ($attr->type === Attr::TYPE_NUMBER) {
                    $cast = 'cast(pa.[[text]] as decimal(10,3))';
                } else {
                    $cast = 'pa.[[text]]';
                }

                $attrQuery = ProdAttr::find()->alias('pa')->select($cast)
                    ->where('pa.[[product_id]]=p.[[product_id]]')
                    ->andWhere(['pa.[[attribute_id]]' => $id]);

                // workaround for condition operator
                $attrExpression = new Expression('(' . $attrQuery->createCommand()->rawSql . ')');

                if (array_key_exists('min', $vals) || array_key_exists('max', $vals)) {
                    if (isset($vals['min'], $vals['max'])) {
                        $query->andWhere(['between', $attrExpression, $vals['min'], $vals['max']]);
                    } elseif (isset($vals['min'])) {
                        $query->andWhere(['>=', $attrExpression, $vals['min']]);
                    } elseif (isset($vals['max'])) {
                        $query->andWhere(['<=', $attrExpression, $vals['max']]);
                    }
                } elseif (!empty($vals)) {
                    $query->andWhere(['in', $attrExpression, $vals]);
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

        //echo $query->createCommand()->rawSql; exit;

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

            $attrVals = Yii::$app->cache->getOrSet([__METHOD__, $this->category_id, $this->recurse, $this->status], function() {
                // получаем карту id характеристик и их значений
                $query = ProdAttr::find()
                    ->alias('pa')
                    ->select('pa.[[attribute_id]], pa.[[text]]')
                    ->distinct(true)
                    ->innerJoin(Prod::tableCateg() . ' p2c', 'p2c.[[product_id]]=pa.[[product_id]]')
                    ->andWhere([
                        'p2c.[[category_id]]' => $this->recurse ? (new Query())->select('cp.[[category_id]]')
                            ->from(Categ::tablePath() . ' cp')
                            ->where(['cp.[[path_id]]' => $this->category_id]) : $this->category_id
                    ]);

                if (isset($this->status)) {
                    $query->innerJoin(Prod::tableName() . ' p', 'p.[[product_id]]=pa.[[product_id]]')
                        ->innerJoin(Categ::tableName() . ' c', 'c.[[category_id]]=p2c.[[category_id]]')
                        ->andWhere([
                            'p.[[status]]' => (int)$this->status,
                            'c.[[status]]' => (int)$this->status
                        ]);
                }

                $attrVals = [];
                foreach ($query->all() as $row) {
                    $attrVals[(int)$row['attribute_id']][] = $row['text'];
                }

                return $attrVals;
            }, null, new TagDependency([
                'tags' => [Attr::class, Categ::class],
                'reusable' => true
            ]));

            // получаем все характеристики из выбранных id
            $this->_categAttrs = ! empty($attrVals) ? Attr::find()->where(['attribute_id' => array_keys($attrVals)])
                ->orderBy('name')->indexBy('attribute_id')->all() : [];

            // расставляем значения
            foreach ($this->_categAttrs as $id => $attr) {
                $vals = array_unique($attrVals[$id]);
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
            $vals = (array)$vals;
            $attr = $attrs[$id] ?? null;
            if (!empty($attr) && !empty($vals)) {
                if (array_key_exists('min', $vals) || array_key_exists('max', $vals)) {
                    $params['a' . $id] = $vals;
                } else {
                    $params['a' . $id] = count($vals) > 1 ? $vals : reset($vals);
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
                if ($attr->type === Attr::TYPE_FLAG) {
                    $params[] = $attr->name;
                } else {
                    $vals = array_unique((array)$this->attrs[$attr->attribute_id]);
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

    /**
     * Замена переменных свойствами категории.
     *
     * @param string $text
     * @return string
     */
    public function replaceVars(string $text)
    {
        return trim(preg_replace_callback('~\${prodFilter\.([^}]+)}~uim', function($matches) {
            return $this->{$matches[1]} ?? '';
        }, $text));
    }
}
