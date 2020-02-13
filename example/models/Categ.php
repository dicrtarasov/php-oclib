<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 11.02.20 02:57:39
 */

declare(strict_types = 1);

namespace app\models;

use Exception;
use Html;
use Registry;
use StringHelper;
use Url;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;
use function array_intersect;
use function array_key_exists;
use function array_keys;
use function count;
use function end;
use function explode;
use function implode;
use function in_array;
use function preg_replace_callback;
use function reset;
use function trim;

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
 * // Relations
 *
 * @property-read CategDesc $desc описание категории
 * @property-read Categ|null $parent
 * @property-read Categ[] $childs
 * @property-read Prod[] $prods
 *
 * // виртуальные свойства
 *
 * @property-read int $childsCount
 * @property-read bool $hasChilds
 * @property-read int $prodsCount
 * @property-read bool $hasProds
 * @property-read bool $isEmpty
 * @property-read array $url URL категории с параметрами
 * @property-read string $href URL страницы категории
 * @property-read string $imageRecurse поиск каринки $image рекурсвно вверх
 * @property-read string $imageUrl URL каринки
 * @property-read string[] $path путь id => name
 * @property-read int $level уровень категории
 * @property-read string $pathName полный путь через '/'
 * @property-read int $topCategId id верхней категории без получения самой категории
 * @property-read bool $isTopCateg
 * @property-read Categ $topCateg
 * @property-read bool $isCable принадлежи к каегориям кабелей
 * @property-read array $breadcrumbs хлебные крошки
 * @property-read bool $isHiddenForCountry спрятана для текущей страны
 * @property-read bool $isEnabled рекурсивная проверка статуса
 * @property-read Prod[] $frontProds витринные товары категории для показа в качестве примерных
 * @property-read string|null $glushImage картинка для сраницы товаров
 * @property-read string $units единицы измерения товаров
 * @property-read string $parentName короткое название родительской категории
 * @property-read string $parentFullName полное название родительской категории
 * @property-read array $crossSib перекрестные ссылки на смежные категории
 * @property-read array $crossUp перекрестные ссылки на категории выше
 *
 * // проксируемые свойства из CategDec
 *
 * @property string $name короткое название категории
 * @property string $fullName полное название категории
 * @property string $singular единичное название товара в категории
 * @property string $description
 * @property string $description2
 * @property bool $isMarka
 * @property string $microrazm микроразметка html-текст
 * @property string $primen применение
 * @property string $rightcol правая колонка
 * @property string $catmenimg
 * @property string $metaTitle title страницы
 * @property string $metaDesc Meta Description
 * @property string $metaH1 заголовок H1
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

    /** @var string рекурсивный вверх $image */
    private $_imageRecurse;

    /** @var string рекурсивное единичное название товара */
    private $_singular;

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
        return '{{%category}}';
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function rules()
    {
        return [
            ['sort_order', 'default', 'value' => 999],
            ['sort_order', 'integer', 'min' => - 999, 'max' => 999]
        ];
    }

    /**
     * Возвращает описание категории.
     *
     * @return ActiveQuery
     * @noinspection PhpUnused
     */
    public function getDesc()
    {
        return $this->hasOne(CategDesc::class, ['category_id' => 'category_id'])->inverseOf('categ');
    }

    /**
     * Возвращает родительскую категорию.
     *
     * @return ActiveQuery
     * @noinspection PhpUnused
     */
    public function getParent()
    {
        return $this->hasOne(self::class, ['category_id' => 'parent_id'])->cache(true, new TagDependency([
            'tags' => [self::class]
        ]));
    }

    /**
     * Короткое имя родиельской категории.
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getParentName()
    {
        $parent = $this->parent;

        return $parent !== null ? $parent->name : '';
    }

    /**
     * Полное имя родиельской категории.
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getParentFullName()
    {
        $parent = $this->parent;

        return $parent !== null ? $parent->fullName : '';
    }

    /**
     * Возвращает связь с дочерними категориями.
     *
     * @return ActiveQuery
     */
    public function getChilds()
    {
        return $this->hasMany(self::class, ['parent_id' => 'category_id'])->inverseOf('parent')->indexBy('category_id');
    }

    /**
     * Возвращает запрос оваров.
     *
     * @return ActiveQuery
     * @throws InvalidConfigException
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
     * Таблица путей.
     *
     * @return string
     */
    public static function tablePath()
    {
        return '{{oc_category_path}}';
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
            $query->rightJoin(Prod::tableName() . ' p', 'p.[[product_id]]=p2c.[[product_id]]')
                ->andWhere(['p.[[status]]' => (int)$options['status']]);
        }

        $query->cache(true, new TagDependency([
            'tags' => [self::class]
        ]));

        return (int)$query->count();
    }

    /**
     * Возвращает путь категории без ее загрузки.
     *
     * @param int $category_id
     * @return array id => name
     */
    public static function path(int $category_id)
    {
        if ($category_id < 1) {
            throw new InvalidArgumentException('category_id');
        }

        return Yii::$app->cache->getOrSet([__METHOD__, $category_id], static function() use ($category_id) {
            return CategDesc::find()->alias('cd')
                ->innerJoin(Categ::tablePath() . ' cp', 'cp.[[path_id]]=cd.[[category_id]]')
                ->select([
                    'name' => 'cd.[[name]]',
                    'path_id' => 'cp.[[path_id]]'
                ])
                ->where(['cp.[[category_id]]' => $category_id])
                ->andWhere(['>', 'ifnull(cp.[[path_id]],0)', 0])
                ->orderBy('cp.[[level]]')
                ->indexBy('path_id')
                ->column();
        }, null, new TagDependency([
            'tags' => [__CLASS__, CategDesc::class]
        ]));
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
        if (empty($this->category_id)) {
            return [];
        }

        if (! isset($this->_path)) {
            // получаем путь из таблицы category_path - слишком долго
            //$this->_path = self::path($this->category_id);

            // получаем путь из родительской категории
            $this->_path = Yii::$app->cache->getOrSet([__METHOD__, (int)$this->category_id], function() {
                $parent = $this->parent;
                $path = $parent !== null ? $parent->path : [];
                $path[$this->category_id] = $this->name;

                return $path;
            }, null, new TagDependency([
                'tags' => [__CLASS__]
            ]));
        }

        return $this->_path;
    }

    /**
     * Возвращает уровень категории.
     *
     * @return int
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     */
    public function getPathName(string $glue = null)
    {
        return implode($glue ?? '/', $this->path);
    }

    /**
     * Возвращает ID верхней категории.
     *
     * @return int
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     */
    public function getIsTopCateg()
    {
        return empty((int)$this->parent_id);
    }

    /**
     * Возвращает главную категорию.
     *
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     */
    public function getIsCable()
    {
        return in_array($this->topCategId, [self::ID_CABLEPROV, self::ID_IMPORTCABLE], true);
    }

    /**
     * Возвращает хлебные крошки.
     *
     * @return array
     * @noinspection PhpUnused
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
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsHiddenForCountry(string $country = null)
    {
        $hiddens = self::hiddens($country);

        return count(array_intersect($hiddens, array_keys($this->path))) > 0;
    }

    /**
     * Возвращает скрытые категории для текущей страны.
     *
     * @param string|null $country
     * @return int[]
     */
    public static function hiddens(string $country = null)
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
     * @noinspection PhpUnused
     */
    public function getIsEnabled()
    {
        // проверяем наличие запрета локально
        if (! $this->status || $this->isHiddenForCountry) {
            return false;
        }

        if (empty($this->category_id)) {
            return false;
        }

        // проверяем наличие запрета у родительских
        if ($this->_isEnabled === null) {
            $this->_isEnabled = Yii::$app->cache->getOrSet([__METHOD__, (int)$this->category_id], function() {
                $parent = $this->parent;

                return $parent !== null ? (int)$parent->isEnabled : 1;
            }, null, new TagDependency([
                'tags' => [self::class]
            ]));
        }

        return (bool)$this->_isEnabled;
    }

    /**
     * Возвращает URL товара с параметрами
     *
     * @param array|null $params
     * @return array
     */
    public function getUrl(array $params = null)
    {
        $params = $params ?: [];
        $params[0] = 'product/category';
        $params['category_id'] = $this->category_id;

        return $params;
    }

    /** @var string */
    private $_href;

    /**
     * Возвращает ссылку на страницу товара.
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getHref()
    {
        if (! isset($this->_href)) {
            $this->_href = Url::to($this->url);
        }

        return $this->_href;
    }

    /**
     * Возвращает каринку, рекурсивно вверх.
     *
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getImageRecurse()
    {
        if ($this->image !== null && $this->image !== '') {
            return $this->image;
        }

        if (empty($this->category_id)) {
            return null;
        }

        if (! isset($this->_imageRecurse)) {
            $this->_imageRecurse = Yii::$app->cache->getOrSet([__METHOD__, (int)$this->category_id], function() {
                $parent = $this->parent;
                $image = $parent !== null ? $parent->imageRecurse : '';
                if (empty($image)) {
                    if ($this->topCategId === self::ID_CABLEPROV) {
                        $image = 'kabs-gl.svg';
                    } elseif ($this->topCategId === self::ID_IMPORTCABLE) {
                        $image = 'catalog/import_cabel-zh.svg';
                    }
                }

                return $image;
            }, null, new TagDependency([
                'tags' => [__CLASS__]
            ]));
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

    /** @var string */
    private $_name;

    /**
     * Короткое название категории.
     *
     * @return string
     */
    public function getName()
    {
        if (! isset($this->_name)) {
            $desc = $this->desc;
            $this->_name = isset($desc) ? $desc->name : '';
        }

        return $this->_name;
    }

    /**
     * Для сорхранения поля name при выборке по join.
     *
     * @param string $name
     * @noinspection PhpUnused
     */
    public function setName(string $name)
    {
        $this->_name = $name;
    }

    /**
     * Полное название категории.
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getFullName()
    {
        $desc = $this->desc;
        if ($desc === null) {
            return '';
        }

        return $desc->full_name ?: $desc->name;
    }

    /**
     * Возвращает единичное название товара рекурсивно
     *
     * @param bool $recurse
     * @return string
     * @noinspection PhpUnused
     */
    public function getSingular(bool $recurse = true)
    {
        if ($this->_singular === null) {
            $desc = $this->desc;
            $this->_singular = isset($desc) ? $desc->singular : '';

            if (empty($this->_singular) && $recurse) {
                $parent = $this->parent;
                if ($parent !== null) {
                    $this->_singular = $parent->getSingular();
                }
            }
        }

        return $this->_singular;
    }

    /**
     * Возвращает первое описание категории.
     *
     * @return string html
     * @noinspection PhpUnused
     */
    public function getDescription()
    {
        $desc = $this->desc;
        if ($desc === null) {
            return '';
        }

        $html = [];

        if (Html::hasText($desc->description)) {
            $html[] = Html::decode($desc->description);
        }

        if ($desc->template_text) {
            $html[] = '<p>Уточнить цены на ' . Html::esc($desc->h1_shab ?: StringHelper::mb_lcfirst($this->fullName)) .
                ', изучить технические характеристики, описание и сферы применения, рассчитать стоимость доставки в свой регион и заказать нужные маркоразмеры вы можете, выбрав необходимую марку в каталоге.</p>';
        }

        return ! empty($html) ? City::replaceVars(implode('', $html)) : '';
    }

    /**
     * Возвращает второе описание категории.
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getDescription2()
    {
        $desc = $this->desc;
        if ($desc === null) {
            return '';
        }

        $html = [];

        if (Html::hasText($desc->description2)) {
            $html[] = Html::decode($desc->description2);
        }

        if ($desc->template_text) {
            $html[] =
                '<p>Купить ' . Html::esc($desc->h1_shab2 ?: StringHelper::mb_lcfirst($this->fullName)) . ' оптом ' .
                Html::esc(City::current()->name3) .
                ' вы можете в РТК «Новые Технологии». Специалисты компании окажут квалифицированную помощь в выборе продукции с учетом технических требований и ответят на все интересующие вопросы.</p>';
        }

        return empty($html) ? '' : City::replaceVars(implode('', $html));
    }

    /**
     * Проверяет наличие категории id в пути.
     *
     * @param int $category_id
     * @return bool
     */
    public function inPath(int $category_id)
    {
        return array_key_exists($category_id, $this->path);
    }

    /** @var string */
    private $_metaH1;

    /**
     * Возвращает H1 (heading_title) страницы.
     *
     * @return string
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function getMetaH1()
    {
        if (! isset($this->_metaH1)) {
            // получаем H1 их ЧПУ алиаса

            /** @var UrlAlias|null $alias */
            $alias = Yii::$app->get('urlAlias', false);

            $meta = $alias ? trim($alias->meta_h1) : null;
            if (empty($meta)) {
                // получаем H1 из category_description
                $desc = $this->desc;
                $meta = $desc ? trim($desc->meta_h1) : null;
                if (empty($meta)) {
                    // генерируем H1 из полного названия категории
                    $meta = '${categ.fullName}';
                }
            }

            $meta = $this->replaceVars($meta);
            $meta = City::replaceVars($meta);
            $this->_metaH1 = $meta;
        }

        return $this->_metaH1;
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
     * Добавляет номер страницы к метатегу.
     *
     * @param string $meta метатег
     * @param int $page номер текущей страницы
     * @return string метатег
     */
    protected static function addPage(string $meta, int $page)
    {
        if ($page > 1) {
            $meta .= ', стр. №' . $page;
        }

        return $meta;
    }

    /**
     * Возвращает meta_title категории.
     *
     * @param array $args
     * @return string
     * @throws InvalidConfigException
     */
    public function getMetaTitle(array $args = [])
    {
        if (! isset($this->_metaTitle)) {
            /** @var UrlAlias|null $alias */
            $alias = Yii::$app->get('urlAlias', false);

            /** @var ProdFilter $prodFilter */
            $prodFilter = $args['prodFilter'] ?? null;

            /** @var CategDesc $desc */
            $desc = $this->desc;

            /** @var int $page */
            $page = (int)($args['page'] ?? 1);

            // прописано в ЧПУ
            if ($alias !== null && ! empty($alias->meta_desc)) {
                $meta = $alias->meta_title;
                self::addPage($meta, $page);
            }
            //
            // страницы фильтров
            elseif ($prodFilter !== null && ! empty($prodFilter->filterText)) {
                $meta = '${categ.fullName} | ${prodFilter.filterText} купить оптом ${city.name3}, цены';
                self::addPage($meta, $page);
            }
            //
            // прописано в категории
            elseif ($desc !== null && ! empty($desc->meta_title)) {
                $meta = $this->desc->meta_title;
                self::addPage($meta, $page);
            }
            //
            // пагинация
            elseif ($page > 1) {
                $meta = '${categ.fullName}, стр. №' . $page . ' - РТК «Новые технологии» ${city.name3}';
            }
            //
            // марки кабелей
            elseif ($this->isMarka && $this->level > 2) {
                $meta = '${categ.fullName} купить ${city.name3}, низкие цены на кабель/провод';
            }
            //
            // по-умолчанию
            else {
                $meta = '${categ.fullName} купить оптом ${city.name3}, цены';
            }

            $meta = $this->replaceVars($meta);
            $meta = City::replaceVars($meta);
            if ($prodFilter !== null) {
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
     * @throws InvalidConfigException
     */
    public function getMetaDesc(array $args = [])
    {
        if (! isset($this->_metaDesc)) {
            /** @var UrlAlias|null $alias */
            $alias = Yii::$app->get('urlAlias', false);

            /** @var ProdFilter $prodFilter */
            $prodFilter = $args['prodFilter'] ?? null;

            /** @var CategDesc $desc */
            $desc = $this->desc;

            /** @var int $page */
            $page = (int)($args['page'] ?? 1);

            // прописано в ЧПУ
            if ($alias !== null && ! empty($alias->meta_desc)) {
                $meta = $alias->meta_desc;
                self::addPage($meta, $page);
            }
            //
            // страницы фильтров
            elseif ($prodFilter !== null && ! empty($prodFilter->filterText)) {
                $meta = 'РТК «НТ» ${city.name3} предлагает: ✅Купить ' . StringHelper::mb_lcfirst($this->fullName) .
                    ', ${prodFilter.filterText} ✅Узнать цены ✆${city.firstPhone}';
                self::addPage($meta, $page);
            }
            //
            // прописано в категории
            elseif ($desc !== null && ! empty($desc->meta_description)) {
                $meta = $this->desc->meta_description;
                self::addPage($meta, $page);
            }
            //
            // пагинация
            elseif ($page > 1) {
                // для марок кабелей
                if ($this->isMarka) {
                    // для страниц маркоразмеров
                    $meta =
                        'Доступные маркоразмеры ' . StringHelper::mb_lcfirst($this->fullName) . ' - Каталог, стр. №' .
                        $page . '. РТК «Новые технологии» ${city.name3}';
                }
                //
                // для остальных страниц
                else {
                    $meta = '${categ.fullName}, стр. №' . $page .
                        '. Полный каталог на сайте РТК «Новые технологии» ${city.name3}';
                }
            }
            //
            // по-умолчанию
            else {
                $meta = '«РТК Новые технологии» ${city.name3} предлагает: ✅Купить ' .
                    StringHelper::mb_lcfirst($this->fullName) .
                    ' ✅Уточнить наличие и оптовые цены ✆${city.firstPhone}';
            }

            $meta = $this->replaceVars($meta);
            $meta = City::replaceVars($meta);

            if ($prodFilter !== null) {
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
     * @noinspection PhpUnused
     */
    public function getIsMarka()
    {
        if (! $this->isCable) {
            return false;
        }

        $desc = $this->desc;

        return isset($desc) ? (bool)$desc->marka : false;
    }

    /**
     * Возвращает микроразметку категории.
     *
     * @return string html
     * @noinspection PhpUnused
     */
    public function getMicrorazm()
    {
        $desc = $this->desc;

        return $desc ? City::replaceVars($desc->microrazm) : '';
    }

    /**
     * Возвращает текст применения.
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getPrimen()
    {
        $desc = $this->desc;

        return $desc ? City::replaceVars($desc->primen) : '';
    }

    /**
     * Возвращает текс правой колонки.
     *
     * @return string
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     */
    public function getCatmenimg()
    {
        $desc = $this->desc;

        return $desc !== null ? $desc->catmenimg : '';
    }

    /**
     * Возвращает витринные товары категории.
     *
     * @param int $count
     * @return Prod[]
     * @noinspection PhpUnused
     */
    public function getFrontProds(int $count = 5)
    {
        $q = (new ProdFilter([
            'category_id' => $this->category_id,
            'recurse' => true,
            'status' => 1
        ]))->query->orderBy(new Expression('p.[[sort_order]], if(p.[[price]]>0,0,1)'))
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
     * @noinspection PhpUnused
     */
    public function getGlushImage()
    {
        /** @var string[] $map */
        $map = [
            59 => 'gbi.svg',
            62 => 'mufti_konc.svg',
            63 => 'mufti_soed.svg',
            64 => 'ogranich.svg',
            65 => 'opori_der.svg',
            66 => 'opori_gb.svg',
            67 => 'opori_met.svg',
            68 => 'transformatory.svg',
            69 => 'armatura.svg',
            70 => 'nagrevat.svg',
            5557 => 'mufti_soed.svg',
            5558 => 'mufti_konc.svg',
            5658 => 'ehz.svg',
            5703 => 'hangar.svg',
        ];

        $img = $map[$this->category_id] ?? null;

        return ! empty($img) ? '/image/catalog/cat_glush/' . $img : null;
    }

    /**
     * Возвращает единицы измерения товаров.
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getUnits()
    {
        return $this->isCable ? 'м' : 'шт';
    }

    /**
     * Перекрестные ссылки на смежные категории.
     *
     * @param int $count
     * @return array [label => '...", 'url' => '...']
     * @noinspection PhpUnused
     */
    public function getCrossSib(int $count = 6)
    {
        if (empty($this->category_id)) {
            return [];
        }

        $data = Yii::$app->cache->getOrSet([__METHOD__, (int)$this->category_id], function() use ($count) {
            return Categ::find()->alias('c')
                ->joinWith('desc cd', false)
                ->select([
                    'name' => 'cd.[[name]]',
                    'category_id' => 'c.[[category_id]]',
                ])
                ->where([
                    'c.[[parent_id]]' => $this->parent_id,
                    'c.[[status]]' => 1
                ])
                ->andWhere([
                    'not', [
                        'c.[[category_id]]' => $this->category_id
                    ]
                ])
                ->orderBy(new Expression('rand()'))
                ->limit($count)
                ->indexBy('category_id')
                ->column();
        }, null, new TagDependency([
            'tags' => [self::class, CategDesc::class]
        ]));

        $links = [];
        foreach ($data as $id => $name) {
            $links[] = [
                'label' => $name,
                'url' => Registry::app()->url->link('product/category', ['category_id' => $id])
            ];
        }

        return $links;
    }

    /**
     * Перекрестные ссылки на уровень вверх.
     *
     * @param int $count
     * @return array [label => '...', 'url' => '...']
     * @noinspection PhpUnused
     */
    public function getCrossUp(int $count = 6)
    {
        if (empty($this->category_id)) {
            return [];
        }

        $data = Yii::$app->cache->getOrSet([__METHOD__, (int)$this->category_id], function() use ($count) {
            $parent = $this->parent;
            if ($parent === null) {
                return [];
            }

            /** @var Categ[] $categs */
            return Categ::find()->alias('c')
                ->joinWith('desc cd', false)
                ->select([
                    'name' => 'cd.[[name]]',
                    'category_id' => 'c.[[category_id]]',
                ])
                ->where([
                    'c.[[parent_id]]' => $parent->parent_id,
                    'c.[[status]]' => 1
                ])
                ->orderBy(new Expression('rand()'))
                ->limit($count)
                ->indexBy('category_id')
                ->column();
        }, null, new TagDependency([
            'tags' => [self::class, CategDesc::class]
        ]));

        $links = [];
        foreach ($data as $id => $name) {
            $links[] = [
                'label' => $name,
                'url' => Registry::app()->url->link('product/category', ['category_id' => $id])
            ];
        }

        return $links;
    }
}
