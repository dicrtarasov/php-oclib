<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\models;

use Html;
use Registry;
use Yii;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;
use function array_key_exists;
use function count;
use function in_array;

/**
 * Категория.
 *
 * @property-read int $category_id
 * @property int $parent_id
 * @property bool $status
 * @property string $image
 * @property string $date_modified
 * @property int $sort_order [int(3)]
 * @property string $yml_image [varchar(255)]
 * @property int $manufacturer_id [int(11) unsigned]
 * @property bool $top [tinyint(1) unsigned]
 * @property int $column [int(3) unsigned]
 * @property string $date_added [datetime]
 *
 * // relations
 *
 * @property-read \app\models\CategDesc $desc описание категории
 * @property-read \app\models\Categ|null $parent
 * @property-read \app\models\Categ[] $childs
 * @property-read \app\models\Prod[] $prods
 *
 * // виртуальные свойства
 *
 * @property-read int $childsCount
 * @property-read bool $hasChilds
 * @property-read int $prodsCount
 * @property-read bool $hasProds
 * @property-read bool $isEmpty
 * @property-read string $url URL категории
 * @property-read string $imageRecurse поиск каринки $image рекурсвно вверх
 * @property-read string $imageUrl URL каринки
 * @property-read string[] $path путь id => name
 * @property-read int $level уровень категории
 * @property-read string $pathName полный путь через '/'
 * @property-read int $topCategId id верхней категории без получения самой категории
 * @property-read bool $isTopCateg
 * @property-read \app\models\Categ $topCateg
 * @property-read bool $isCable принадлежи к каегориям кабелей
 * @property-read array $breadcrumbs хлебные крошки
 * @property-read bool $isHiddenForCountry спрятана для текущей страны
 * @property-read bool $isEnabled рекурсивная проверка статуса
 * @property-read \app\models\Prod[] $frontProds витринные товары категории для показа в качестве примерных
 * @property-read string|null $glushImage картинка для сраницы товаров
 * @property-read string $units единицы измерения товаров
 * @property-read string|null $parentName название родительской категории
 *
 * // проксируемые свойства из CategDec
 *
 * @property string $name
 * @property string $singular единичное название товара в категории
 * @property string $description
 * @property string $description2
 * @property bool $isMarka
 * @property string $microrazm микроразметка html-текст
 * @property string $primen применение
 * @property string $rightcol правая колонка
 * @property string $catmenimg
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Categ extends ActiveRecord
{
    /** @var int Железобетонные изделия ЖБИ */
    public const ID_ZHBI = 59;

    /** @var int Кабели и провода */
    public const ID_CABLEPROV = 60;

    /** @var int Муфты кабельные / Муфты концевые */
    public const ID_MUFTY_KONCEV = 62;

    /** @var int Муфты кабельные / Муфты соединительные */
    public const ID_MUFTY_SOED = 63;

    /** @var int Железобетонные опоры ЛЭП */
    public const ID_ZHBOPORY = 66;

    /** @var int Импортный кабель */
    public const ID_IMPORTCABLE = 5885;

    /** @var int Блочные тепловые пункты */
    public const ID_BLOCHTEP = 6467;

    /** @var int[][] категории скрытые для определенных стран */
    public const HIDDENS_BY_COUNTRY = [
        'kz' => [self::ID_ZHBI, self::ID_ZHBOPORY],
    ];

    /** @var string[] путь категории */
    private $_path;

    /** @var self */
    private $_topCateg;

    /** @var int расчетное значение рекурсивного статуса и скрытости */
    private $_isEnabled;

    /** @var string */
    private $_url;

    /** @var string рекурсивный вверх $image */
    private $_imageRecurse;

    /** @var string рекурсивное единичное название товара */
    private $_singular;

    /** @var string */
    private $_metaH1;

    /** @var @var string */
    private $_metaTitle;

    /** @var string */
    private $_metaDesc;

    /**
     * Таблица.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{oc_category}}';
    }

    /**
     * Связи с городами.
     *
     * @return string
     */
    public static function tableCities()
    {
        return '{{oc_category_to_city}}';
    }

    /**
     * Возвращает описание категории.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDesc()
    {
        return $this->hasOne(CategDesc::class, ['category_id' => 'category_id'])->inverseOf('categ');
    }

    /**
     * Возвращает родительскую категорию.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(self::class, ['category_id' => 'parent_id'])->cache(true, new TagDependency([
            'tags' => [self::class]
        ]));
    }

    /**
     * Возвращает имя родиельской категории.
     *
     * @return string|null
     */
    public function getParentName()
    {
        $path = array_values($this->path);
        return count($path) > 1 ? reset($path) : null;
    }

    /**
     * Возвращает связь с дочерними категориями.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChilds()
    {
        return $this->hasMany(self::class, ['parent_id' => 'category_id'])->inverseOf('parent')->indexBy('category_id');
    }

    /**
     * Возвращает запрос оваров.
     *
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getProds()
    {
        return $this->hasMany(Prod::class, ['product_id' => 'product_id'])
            ->viaTable(Prod::tableCateg(), ['category_id' => 'category_id'])
            ->inverseOf('categ')
            ->indexBy('product_id');
    }

    /**
     * Проверяет является ли каегория пустой (не содержит подкаегорий и товаров).
     *
     * @param array $options
     * - bool $recursive
     * - bool $status
     * @return bool
     */
    public function getIsEmpty(array $options = null)
    {
        if ($options === null) {
            $options = [
                'recurse' => false,
                'status' => 1
            ];
        }

        return ! $this->getHasChilds($options) && ! $this->getHasProds($options);
    }

    /**
     * Проверяет имеются ли дочерние категории.
     *
     * @param array $options
     * @return bool
     */
    public function getHasChilds(array $options = null)
    {
        return $this->getChildsCount($options) > 0;
    }

    /**
     * Проверяет наличие товаров.
     *
     * @param array $options
     * @return bool
     * @see getProdsCount(array $options)
     */
    public function getHasProds(array $options = null)
    {
        return $this->getProdsCount($options) > 0;
    }

    /**
     * Возвращает количество дочерних категорий.
     *
     * @param array $options
     * - bool $recursive
     * - bool $status
     * @return int
     */
    public function getChildsCount(array $options = null)
    {
        if ($options === null) {
            $options = [
                'status' => 1,
                'recursive' => false
            ];
        }

        $query = self::find()->alias('c')->where([
            'c.[[parent_id]]' => empty($options['recursive']) ? $this->category_id :
                (new Query())->select('cp.[[category_id]]')
                    ->from(self::tablePath() . ' cp')
                    ->where(['cp.[[path_id]]' => $this->category_id])
        ]);

        if (isset($options['status'])) {
            $query->andWhere(['c.[[status]]' => (int)$options['status']]);
        }

        $query->cache(true, new TagDependency([
            'tags' => [self::class]
        ]));

        return $query->count();
    }

    /**
     * Возращает кол-во товаров в категории.
     *
     * @param array $options
     * - bool $recursive
     * - bool $status
     * @return int
     */
    public function getProdsCount(array $options = null)
    {
        if (! isset($options)) {
            $options = [
                'status' => 1,
                'recursive' => false
            ];
        }

        $query = (new Query())->from(Prod::tableCateg() . ' p2c')->where([
            'p2c.[[category_id]]' => empty($options['recurse']) ? $this->category_id :
                (new Query())->select('cp.[[category_id]]')
                    ->from(self::tablePath() . ' cp')
                    ->where(['cp.[[path_id]]' => $this->category_id])

        ]);

        if (isset($options['status'])) {
            $query->innerJoin(Prod::tableName() . ' p', 'p.[[product_id]]=p2c.[[product_id]]')
                ->andWhere(['p.[[status]]' => (int)$options['status']]);
        }

        $query->cache(true, new TagDependency([
            'tags' => [self::class]
        ]));

        return (int)$query->count();
    }

    /**
     * Таблица путей.
     *
     * @return string
     */
    public static function tablePath()
    {
        return '{{oc_category_path}}';
    }

    /**
     * Возвращает путь категории.
     *
     * [
     *   category_id => name,
     *   category_id => name
     * ]
     *
     * @return array
     */
    public function getPath()
    {
        if (! isset($this->_path)) {
            $this->_path = self::path($this->category_id);
        }

        return $this->_path;
    }

    /**
     * Возвращает путь категории без ее загрузки.
     *
     * @param int $category_id
     * @return array id => name
     */
    public static function path(int $category_id)
    {
        return (new Query())->select(['cd.[[name]]', 'cp.[[path_id]]'])
            ->from(self::tablePath() . ' cp')
            ->innerJoin(CategDesc::tableName() . ' cd', 'cd.[[category_id]]=cp.[[path_id]]')
            ->where(['cp.[[category_id]]' => $category_id])
            ->andWhere('ifnull(cp.[[path_id]],0) > 0')
            ->orderBy('cp.[[level]]')
            ->indexBy('path_id')
            ->cache(true, new TagDependency([
                'tags' => [self::class]
            ]))
            ->column();
    }

    /**
     * Возвращает уровень категории.
     *
     * @return int
     */
    public function getLevel()
    {
        return count($this->path);
    }

    /**
     * Возвращает полный путь категории.
     *
     * @param string|null $glue
     * @return string
     */
    public function getPathName(string $glue = null)
    {
        return implode($glue ?? '/', array_values($this->path));
    }

    /**
     * Возвращает ID верхней категории.
     *
     * @return int
     */
    public function getTopCategId()
    {
        $path = array_keys($this->path);
        return (int)reset($path);
    }

    /**
     * Проверяет является ли категория верхнего уровня.
     *
     * @return bool
     */
    public function getIsTopCateg()
    {
        return empty((int)$this->parent_id);
    }

    /**
     * Возвращает главную категорию.
     *
     */
    public function getTopCateg()
    {
        // если это и есть верхняя категория
        if ($this->isTopCateg) {
            return $this;
        }

        if (! isset($this->_topCateg)) {
            $this->_topCateg = self::findOne(['category_id' => $this->topCategId]);
        }

        return $this->_topCateg;
    }

    /**
     * Возвращает признак категории кабелей.
     *
     * @return bool
     */
    public function getIsCable()
    {
        return in_array($this->topCategId, [self::ID_CABLEPROV, self::ID_IMPORTCABLE], true);
    }

    /**
     * Возвращает хлебные крошки.
     *
     * @return array
     */
    public function getBreadcrumbs()
    {
        $breadcrumbs = [];

        foreach ($this->path as $id => $name) {
            $breadcrumbs[] = [
                'text' => $name,
                'href' => Registry::app()->url->link('product/category', ['category_id' => $id]),
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Проверяет спрятана ли категория для текущей страны.
     *
     * @param string|null $country
     * @return boolean
     */
    public function getIsHiddenForCountry(string $country = null)
    {
        $hiddens = self::getHiddens($country);
        return count(array_intersect($hiddens, array_keys($this->path))) > 0;
    }

    /**
     * Возвращает скрытые категории для текущей страны.
     *
     * @param string|null $country
     * @return int[]
     */
    public static function getHiddens(string $country = null)
    {
        if ($country === null) {
            $parts = explode('.', $_SERVER['HTTP_HOST'] ?? '');
            $country = end($parts);
        }

        return self::HIDDENS_BY_COUNTRY[$country] ?? [];
    }

    /**
     * Возвращает сатус рекурсивно.
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        // проверяем наличие запрета локально
        if (! $this->status || $this->isHiddenForCountry) {
            return false;
        }

        // проверяем наличие запрета у родительских
        if ($this->_isEnabled === null) {
            $this->_isEnabled = Yii::$app->cache->getOrSet([__METHOD__, $this->category_id], function() {
                $parent = $this->parent;
                return isset($parent) ? (int)$parent->isEnabled : 1;
            }, null, new TagDependency([
                'tags' => [self::class]
            ]));
        }

        return (bool)$this->_isEnabled;
    }

    /**
     * Возвращает URL категории.
     *
     * @param array $params дополнительные парамеры запроса.
     * @return string
     */
    public function getUrl(array $params = null)
    {
        if ($params === null) {
            $params = [];
        }

        if (count($params)) {
            return Registry::app()->url->link('product/category', array_merge($params, [
                'category_id' => $this->category_id,
            ]));
        }

        if (! isset($this->_url)) {
            $this->_url = Registry::app()->url->link('product/category', [
                'category_id' => $this->category_id,
            ]);
        }

        return $this->_url;
    }

    /**
     * Возвращает каринку, рекурсивно вверх.
     *
     * @return string
     */
    public function getImageRecurse()
    {
        if ($this->image !== '') {
            return $this->image;
        }

        if (! isset($this->_imageRecurse)) {
            $this->_imageRecurse = Yii::$app->cache->getOrSet([__METHOD__, $this->category_id], function() {
                $parent = $this->parent;
                return isset($parent) ? $parent->imageRecurse : '';
            });
        }

        return $this->_imageRecurse;
    }

    /**
     * Возвращает URL каринки.
     *
     * @param array $options опции
     * - bool $recurse рекурсивный поиск каринки
     * @return string
     */
    public function getImageUrl(array $options = null)
    {
        if ($options === null) {
            $options = [
                'recurse' => true
            ];
        }

        $image = ! empty($options['recurse']) ? $this->imageRecurse : $this->image;
        return ! empty($image) ? '/image/' . $image : '';
    }

    /**
     * Возвращает превью картинки рекурсивно.
     *
     * @param int $width
     * @param int $height
     * @param array $options
     * @return string
     * @throws \yii\base\Exception
     */
    public function thumb(int $width, int $height, array $options = null)
    {
        if ($options === null) {
            $options = [
                'recurse' => true
            ];
        }

        $model = Registry::app()->load->model('tool/image');
        $image = ! empty($options['recurse']) ? $this->imageRecurse : $this->image;
        return $model->resize($image ?: 'no_image.png', $width, $height);
    }

    /**
     * Возвращает название каегории.
     *
     * @return string
     */
    public function getName()
    {
        $desc = $this->desc;
        return isset($desc) ? $desc->name : '';
    }

    /**
     * Возвращает единичное название товара рекурсивно
     *
     * @param bool $recurse
     * @return string
     */
    public function getSingular(bool $recurse = true)
    {
        if ($this->_singular === null) {
            $desc = $this->desc;
            $this->_singular = isset($desc) ? $desc->singular : '';

            if (empty($this->_singular) && $recurse) {
                $parent = $this->parent;
                if ($parent !== null) {
                    $this->_singular = $parent->getSingular(true);
                }
            }
        }

        return $this->_singular;
    }

    /**
     * Возвращает первое описание категории.
     *
     * @return string html
     * @throws \yii\base\InvalidConfigException
     */
    public function getDescription()
    {
        $desc = $this->desc;
        if (empty($desc)) {
            return '';
        }

        $html = trim(Html::decode($desc->description));
        if (trim(Html::toText($html)) === '') {
            return '';
        }

        return City::replaceVars($html);
    }

    /**
     * Возвращает второе описание категории.
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getDescription2()
    {
        $desc = $this->desc;
        if (empty($desc)) {
            return '';
        }

        $html = trim(Html::decode($desc->description2));
        if (trim(Html::toText($html)) === '') {
            return '';
        }

        return City::replaceVars($html);
    }

    /**
     * Возвращает H1 (heading_title) страницы.
     *
     * @return string
     * @throws \Exception
     */
    public function metaH1()
    {
        if ($this->_metaH1 === null) {
            /** @var \app\models\UrlAlias|null $alias */
            $alias = Yii::$app->get('urlAlias', false);
            $meta = isset($alias) ? trim($alias->meta_h1) : null;
            if (empty($meta)) {
                $desc = $this->desc;
                $meta = isset($desc) ? trim($desc->meta_h1) : null;
                if (empty($meta)) {
                    // TODO очень сранно что добавляеся singular - проверить
                    if (! $this->isTopCateg && $this->inPath(self::ID_CABLEPROV) && ! empty($this->singular)) {
                        $meta = '${categ.singular} . ${categ.name}';
                    } else {
                        $meta = '${categ.name}';
                    }
                }
            }

            $meta = $this->replaceVars($meta);
            $meta = City::replaceVars($meta);
            $this->_metaH1 = $meta;
        }

        return $this->_metaH1;
    }

    /**
     * Проверяет наличие категории id в пути.
     *
     * @param int $category_id
     * @return boolean
     */
    public function inPath(int $category_id)
    {
        return array_key_exists($category_id, $this->path);
    }

    /**
     * Замена переменных свойствами категории.
     *
     * @param string $text
     * @return string
     */
    public function replaceVars(string $text)
    {
        return trim(preg_replace_callback('~\${categ\.([^}]+)}~uim', function($matches) {
            return $this->{$matches[1]} ?? '';
        }, $text));
    }

    /**
     * Возвращает meta_title категории.
     *
     * @param array $args
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function metaTitle(array $args = [])
    {
        if ($this->_metaTitle === null) {
            /** @var \app\models\UrlAlias|null $alias */
            $alias = Yii::$app->get('urlAlias', false);

            /** @var \app\models\ProdFilter $prodFilter */
            $prodFilter = $args['prodFilter'] ?? null;
            $page = (int)Yii::$app->request->get('page', 1);

            if ($page < 2 && isset($alias) && !empty($alias->meta_title)) {
                $meta = $alias->meta_title;
            } elseif (isset($prodFilter) && ! empty($prodFilter->filterText)) {
                // для фильтров
                $meta = '${categ.name} | ${prodFilter.filterText} купить оптом ${city.name3}, цены';
                if ($page > 1) {
                    $meta .= ', стр. №' . $page;
                }
            } elseif ($page > 1) {
                // для пагинаций
                $meta = '${categ.name}, стр. №' . $page . ' - РТК «Новые технологии» ${city.name3}';
            } elseif (! empty($this->desc) && ! empty($this->desc->meta_title)) {
                // если имеется, то возвращаем оригинальный meta title
                $meta = $this->desc->meta_title;
            } elseif ($this->isMarka) {
                // генерируем для маркоразмеров
                $meta =
                    '${categ.name} ${categ.parentName}, купить оптом ${city.name3}, цены - РТК «Новые технологии»';
            } elseif (! $this->isTopCateg && $this->topCategId !== self::ID_CABLEPROV) {
                // для категорий более первого уровня не кабелей
                $meta = '${categ.name}, купить ${city.name3}, цены - РТК «Новые технологии»';
            } else {
                // по-умолчанию
                $meta = '${categ.name} купить оптом ${city.name3}, цены - РТК «Новые технологии»';
            }

            $meta = $this->replaceVars($meta);
            $meta = City::replaceVars($meta);
            if (!empty($prodFilter)) {
                $meta = $prodFilter->replaceVars($meta);
            }

            $this->_metaTitle = $meta;
        }

        return $this->_metaTitle;
    }

    /**
     * Возвращает meta_description сраницы.
     *
     * @param array $args
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function metaDesc(array $args = [])
    {
        if ($this->_metaDesc === null) {
            /** @var \app\models\UrlAlias|null $alias */
            $alias = Yii::$app->get('urlAlias', false);

            /** @var \app\models\ProdFilter $prodFilter */
            $prodFilter = $args['prodFilter'] ?? null;

            $page = (int)Yii::$app->request->get('page', 1);

            if ($page < 2 && isset($alias) && !empty($alias->meta_desc)) {
                $meta = $alias->meta_desc;
            } elseif (! empty($prodFilter) && ! empty($prodFilter->filterText)) {
                // для фильтров
                $meta = 'РТК «НТ» ${city.name3} предлагает: ✅Купить ${categ.name}, ${prodFilter.filterText} ✅Узнать цены ✆${city.firstPhone}';
                if ($page > 1) {
                    $meta .= ', стр. №' . $page;
                }
            } elseif ($page > 1) {
                // для сраниц пагинаций
                if ($this->isMarka) {
                    // для страниц маркоразмеров
                    $meta = 'Доступные маркоразмеры ' . mb_strtolower((string)$this->parentName) .
                            ' ${categ.name} - Каталог, стр. №' . $page . '. РТК «Новые технологии» ${city.name3}';
                } else {
                    // для остальных страниц
                    $meta = '${categ.name}, стр. №' . $page .
                            '. Полный каталог на сайте РТК «Новые технологии» ${city.name3}';
                }
            } elseif (! empty($this->desc) && ! empty($this->desc->meta_description)) {
                // искомое описание
                $meta = $this->desc->meta_description;
            } elseif ($this->isMarka) {
                // генерируем новое описание для страниц маркоразмеров
                $meta =
                    'РТК Новые технологии ${city.name3} предлагает: ✅Купить ${categ.name} ${categ.parentName} ✅Узнать цены на ${categ.name} ✆${city.firstPhone}';
            } elseif (! $this->isTopCateg && $this->topCategId !== self::ID_CABLEPROV) {
                // для категорий второго уровня не кабелей
                $meta =
                    '${categ.name}: купить эту и другую продукцию из категории ${categ.parentName} вы можете в РТК Новые технологии ${city.name3} ✅Узнать цены и оформить заказ: ✆${city.firstPhone}';
            } elseif ($this->level >= 4) {
                // если глубина более 4
                $pathName = array_values($this->path);
                $meta = 'РТК Новые технологии ${city.name3} предлагает: ✅Купить ${categ.name} (категория ' .
                        ($pathName[1] ?? '') . ') ✅Узнать цены на ' . ($pathName[1] ?? '') .
                        ' и другое оборудование ✆${city.firstPhone}';
            } else {
                // по-умолчанию
                $meta =
                    'РТК Новые технологии ${city.name3} предлагает: ✅Купить ${categ.name} ✅Узнать цены на ${categ.name} ✆${city.firstPhone}';
            }

            $meta = $this->replaceVars($meta);
            $meta = City::replaceVars($meta);
            if (!empty($prodFilter)) {
                $meta = $prodFilter->replaceVars($meta);
            }

            $this->_metaDesc = $meta;
        }

        return $this->_metaDesc;
    }

    /**
     * Возвращает признак каегории маркоразмеров кабелей.
     *
     * @return bool
     */
    public function getIsMarka()
    {
        $desc = $this->desc;
        return isset($desc) ? (bool)$desc->marka : false;
    }

    /**
     * Возвращает микроразметку категории.
     *
     * @return string html
     */
    public function getMicrorazm()
    {
        $desc = $this->desc;
        if (empty($desc)) {
            return '';
        }

        $html = trim(Html::decode($desc->microrazm));
        if (trim(Html::toText($html)) === '') {
            return '';
        }

        return City::replaceVars($html);
    }

    /**
     * Возвращает текст применения.
     *
     * @return string
     */
    public function getPrimen()
    {
        $desc = $this->desc;
        if (empty($desc)) {
            return '';
        }

        $html = trim(Html::decode($desc->primen));
        if (trim(Html::toText($html)) === '') {
            return '';
        }

        return City::replaceVars($html);
    }

    /**
     * Возвращает текс правой колонки.
     *
     * @return string
     */
    public function getRightcol()
    {
        // нельзя применять проверку на пустой текст, потому как содержит олько svg
        $desc = $this->desc;
        return isset($desc) ? City::replaceVars($desc->rightcol) : '';
    }

    /**
     * Каринки меню каегории.
     *
     * @return bool|string
     */
    public function getCatmenimg()
    {
        $desc = $this->desc;
        return ! empty($desc) ? $desc->catmenimg : '';
    }

    /**
     * Возвращает ссылки на случайные соседние категории второго уровня.
     *
     * @param int $count
     * @return \app\models\Categ[] заданное количество случайных соседних категорий второго уровня
     */
    public function randomL2(int $count = 6)
    {
        $categs = [];

        // получаем категории второго уровня
        $categsL2 = self::find()->where([
            'parent_id' => $this->topCategId,
            'status' => 1
        ])->cache(true, new TagDependency([
            'tags' => [self::class]
        ]))->all();

        // пока не наберем $count ссылок
        while (! empty($categsL2) && count($categs) < $count) {
            // обходим недостающее количесво случайных ключей
            foreach (array_rand($categsL2, min(count($categsL2), $count - count($categs))) as $key) {
                // удаляем выбранную из списка
                /** @noinspection OffsetOperationsInspection */
                $categ = $categsL2[$key];
                /** @noinspection OffsetOperationsInspection */
                unset($categsL2[$key]);

                // если имя не равно мета H1, то забираем
                if ($categ->name !== $this->desc->meta_h1) {
                    $categs[] = $categ;
                }
            }
        }

        return $categs;
    }

    /**
     * Возвращает витринные товары категории.
     *
     * @param int $count
     * @return \app\models\Prod[]
     */
    public function getFrontProds(int $count = 4)
    {
        $q = (new ProdFilter([
            'category_id' => $this->category_id,
            'recurse' => true,
            'status' => 1
        ]))->query->orderBy(new Expression('if(p.[[price]]>0,0,1), p.[[sort_order]]'))
            ->limit($count)
            ->cache(true, new TagDependency([
                'tags' => [self::class, Prod::class]
            ]))
            ->with(['desc']);

        $prods = $q->all();
        foreach ($prods as &$prod) {
            $prod->populateRelation('categ', $this);
        }

        return $prods;
    }

    /**
     * Возвращает непонятно какую картинку для страницы товара.
     *
     * @return string|null
     */
    public function getGlushImage()
    {
        /** @var string[] $map */
        $map = [
            69 => 'armatura.svg',
            59 => 'gbi.svg',
            66 => 'opori_gb.svg',
            67 => 'opori_met.svg',
            62 => 'mufti_konc.svg',
            63 => 'mufti_soed.svg',
            64 => 'ogranich.svg',
            65 => 'opori_der.svg',
            68 => 'transformatory.svg',
            70 => 'nagrevat.svg',
            5558 => 'mufti_konc.svg',
            5658 => 'ehz.svg',
            5703 => 'hangar.svg',
            5557 => 'mufti_soed.svg',
        ];

        $img = $map[$this->category_id] ?? null;
        return ! empty($img) ? '/image/catalog/cat_glush/' . $img : null;
    }

    /**
     * Возвращает единицы измерения товаров.
     *
     * @return string
     */
    public function getUnits()
    {
        return $this->isCable ? 'м' : 'шт';
    }
}
