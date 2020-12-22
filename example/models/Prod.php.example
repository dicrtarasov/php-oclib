<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 06.12.19 07:13:18
 */

/** @noinspection LongInheritanceChainInspection */
declare(strict_types = 1);

namespace app\models;

use Html;
use Registry;
use StringHelper;
use Url;
use Yii;
use yii\base\Exception;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\behaviors\AttributeTypecastBehavior;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;
use function trim;

/**
 * Товар.
 *
 * @property-read int $product_id
 * @property string $image
 * @property float $price
 * @property bool $status
 *
 * // не используемые
 *
 * @property string $model [varchar(64)]
 * @property string $microrazm
 * @property string $upc [varchar(12)]
 * @property string $ean [varchar(14)]
 * @property string $jan [varchar(13)]
 * @property string $isbn [varchar(17)]
 * @property string $mpn [varchar(64)]
 * @property string $location [varchar(128)]
 * @property int $quantity [int(4) unsigned]
 * @property int $stock_status_id [int(11) unsigned]
 * @property int $manufacturer_id [int(11) unsigned]
 * @property bool $shipping [tinyint(1) unsigned]
 * @property int $points [int(8)]
 * @property int $tax_class_id [int(11) unsigned]
 * @property string $date_available [datetime]
 * @property string $weight [decimal(15,8) unsigned]
 * @property int $weight_class_id [int(11) unsigned]
 * @property string $length [decimal(15,8) unsigned]
 * @property string $width [decimal(15,8) unsigned]
 * @property string $height [decimal(15,8) unsigned]
 * @property int $length_class_id [int(11) unsigned]
 * @property bool $subtract [tinyint(1) unsigned]
 * @property int $minimum [int(11) unsigned]
 * @property int $sort_order [int(11)]
 * @property int $viewed [int(5) unsigned]
 * @property string $date_added [datetime]
 * @property int $date_modified [timestamp]
 * @property int $popularity популярность товара
 *
 * // Relations
 * @property-read ProdDesc $desc
 * @property-read Categ $categ
 * @property-read Manuf $manuf
 * @property-read ProdAttr[] $attrs
 * @property-read UrlAlias $urlAlias
 *
 * // связанные из ProdDesc
 *
 * @property-read string $name короткое название
 * @property-read string $fullName полное название
 * @property-read string $description
 * @property string $shortDescription
 * @property-read string $primen
 * @property-read string $metaH1
 * @property-read string $metaTitle
 * @property-read string $metaDesc
 *
 * // Virtual
 *
 * @property-read array $breadcrumbs
 * @property-read string $imageRecurse
 * @property-read string $imageUrl
 * @property-read array $url
 * @property-read string $href
 * @property-read string $units единицы измерения
 * @property-read string $sku [varchar(32)] не используется
 * @property-read string $ymlImageUrl фиг его знает
 * @property int $categoryId id категории
 * @property-read array $crossSib перекрестные ссылки на смежные товары
 * @property-read array $crossUp перекрестные ссылки на верхние категории
 */
class Prod extends ActiveRecord
{
    /** @var float коэффициент цены для 100 метров кабеля */
    public const DISCOUNT_100 = 0.99;

    /** @var float коэффициент цены для 100 метров кабеля */
    public const DISCOUNT_1000 = 0.98;

    /** @var int идентификатор в корзине */
    public $cartId;

    /** @var int кол-во в корзине */
    public $inCart;

    /**
     * Название таблицы.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{oc_product}}';
    }

    /**
     * Наличие по городам.
     *
     * @return string
     */
    public static function tableCity()
    {
        return '{{prod2locale}}';
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function rules()
    {
        return [
            ['price', 'default', 'value' => 0],
            ['price', 'number', 'min' => 0],

            ['image', 'trim'],
            ['image', 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'typecastAfterFind' => true,
                'typecastAfterValidate' => false
            ]
        ];
    }

    /**
     * Связь с описанием.
     *
     * @return ActiveQuery
     * @noinspection PhpUnused
     */
    public function getDesc()
    {
        return $this->hasOne(ProdDesc::class, ['product_id' => 'product_id'])->inverseOf('prod');
    }

    /** @var UrlAlias|false */
    private $_urlAlias;

    /**
     * Возвращает алиас ЧПУ.
     *
     * @return UrlAlias|null
     * @noinspection PhpUnused
     */
    public function getUrlAlias()
    {
        if (! isset($this->urlAlias)) {
            $this->_urlAlias = UrlAlias::findOne(['query' => 'product_id=' . $this->product_id]) ?: false;
        }

        return $this->_urlAlias ?: null;
    }

    /** @var int|false */
    private $_categoryId;

    /**
     * Возвращает id категории.
     *
     * @return int|null
     * @noinspection PhpUnused
     */
    public function getCategoryId()
    {
        if (empty($this->product_id)) {
            return null;
        }

        if (! isset($this->_category_id)) {
            $this->_categoryId = (new Query())
                ->from(self::tableCateg())
                ->select('category_id')
                ->where(['product_id' => $this->product_id])
                ->limit(1)
                ->cache(true, new TagDependency([
                    'tags' => [self::class, Categ::class]
                ]))
                ->scalar();

            $this->_categoryId = isset($this->_categoryId) ? (int)$this->_categoryId : false;
        }

        return $this->_categoryId === false ? null : $this->_categoryId;
    }

    /**
     * Устанавливает id категории.
     *
     * @param int $categoryId
     * @noinspection PhpUnused
     */
    public function setCategoryId(int $categoryId)
    {
        $this->_categoryId = $categoryId;
    }

    /**
     * Связь с категорией.
     *
     * @return ActiveQuery
     * @throws InvalidConfigException
     * @noinspection PhpUnused
     */
    public function getCateg()
    {
        return $this->hasOne(Categ::class, ['category_id' => 'category_id'])
            ->viaTable(self::tableCateg(), ['product_id' => 'product_id'])
            ->cache(true, new TagDependency([
                'tags' => [self::class, Categ::class]
            ]));
    }

    /**
     * Таблица категорий.
     *
     * @return string
     */
    public static function tableCateg()
    {
        return '{{oc_product_to_category}}';
    }

    /**
     * Получает ID категории для заданного товара без его загрузки.
     *
     * @param int $prod_id
     * @return int|null id категории
     */
    public static function categId(int $prod_id)
    {
        $categ_id = (new Query())->select('category_id')
            ->from(self::tableCateg())
            ->where(['product_id' => $prod_id])
            ->orderBy('category_id')
            ->limit(1)
            ->scalar();

        return $categ_id ? (int)$categ_id : null;
    }

    /**
     * Запрос производителя.
     *
     * @return ActiveQuery
     * @noinspection PhpUnused
     */
    public function getManuf()
    {
        return $this->hasOne(Manuf::class, ['manufacturer_id' => 'manufacturer_id'])->cache(true, new TagDependency([
            'tags' => [self::class, Manuf::class]
        ]));
    }

    /**
     * Возвращает запрос характерисик.
     *
     * @return ActiveQuery
     * @noinspection PhpUnused
     */
    public function getAttrs()
    {
        return $this->hasMany(ProdAttr::class, ['product_id' => 'product_id'])
            ->inverseOf('prod')
            ->indexBy('attribute_id');
    }

    /** @var string */
    private $_imageRecurse;

    /**
     * Возвращает картинку рекурсивно.
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getImageRecurse()
    {
        if (! empty($this->image)) {
            return $this->image;
        }

        if (! isset($this->_imageRecurse)) {
            $categ = $this->categ;
            $this->_imageRecurse = $categ !== null ? $categ->imageRecurse : '';
        }

        return $this->_imageRecurse;
    }

    /**
     * Возвращает URL каринки.
     *
     * @param array $options
     * - bool $recurse рекурсивный поиск картинки
     * @return string
     * @noinspection PhpUnused
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
     * Возвращает URL превью.
     *
     * @param int $width
     * @param int $height
     * @param array|null $options
     * @return string url превью
     * @throws Exception
     */
    public function thumb(int $width, int $height, array $options = null)
    {
        if ($options === null) {
            $options = [
                'recurse' => true
            ];
        }

        $modelToolImage = Registry::app()->load->model('tool/image');

        $image = empty($options['recurse']) ? $this->image : $this->imageRecurse;

        return $modelToolImage->resize($image ?: 'no_image.png', $width, $height);
    }

    /**
     * Возвращает URL страницы товара с параметрами.
     *
     * @param array|null $params
     * @return array
     */
    public function getUrl(array $params = null)
    {
        $params = $params ?: [];
        $params[0] = 'product/product';
        $params['product_id'] = $this->product_id;
        return $params;
    }

    /** @var string ссылка на страницу товара */
    private $_href;

    /**
     * Возвращает ссылку на страницу товара.
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getHref()
    {
        if (!isset($this->_href)) {
            $this->_href = Url::to($this->url);
        }

        return $this->_href;
    }

    /**
     * Название товара.
     *
     * @return string
     */
    public function getName()
    {
        $desc = $this->desc;

        return isset($desc) ? $desc->name : '';
    }

    /**
     * Полное название товара, включая singular из названия категории.
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

        return $desc->name_full ?: $desc->name;
    }

    /**
     * Описание товара.
     *
     * @return string
     * @throws ExitException
     * @noinspection PhpUnused
     */
    public function getDescription()
    {
        $desc = $this->desc;
        if (! isset($desc)) {
            return '';
        }

        $text = trim(Html::decode($desc->description));
        if (trim(Html::toText($text)) === '') {
            return '';
        }

        return City::replaceVars($text);
    }

    /** @var string */
    private $_shortDescription;

    /**
     * Установить короткое описание.
     *
     * @param string $shortDescription
     * @noinspection PhpUnused
     */
    public function setShortDescription(string $shortDescription)
    {
        $this->_shortDescription = $shortDescription;
    }

    /**
     * Короткое описание.
     *
     * @return string
     * @noinspection PhpUnused
     * @throws ExitException
     */
    public function getShortDescription()
    {
        if (! isset($this->_shortDescription)) {
            $desc = $this->desc;
            if ($desc === null || ! Html::hasText($desc->short_description)) {
                $this->_shortDescription = '';
            } else {
                $this->_shortDescription = trim(Html::decode($desc->short_description));
            }
        }

        return City::replaceVars($this->_shortDescription);
    }

    /**
     * Применение товара.
     *
     * @return string
     * @noinspection PhpUnused
     * @throws ExitException
     */
    public function getPrimen()
    {
        $desc = $this->desc;
        if (! isset($desc)) {
            return '';
        }

        $text = Html::decode($desc->primen);
        if (trim(Html::toText($text)) === '') {
            return '';
        }

        return City::replaceVars($text);
    }

    /**
     * H1 страницы.
     *
     * @return string
     * @throws InvalidConfigException
     * @throws ExitException
     * @noinspection PhpUnused
     */
    public function getMetaH1()
    {
        /** @var UrlAlias|null $alias */
        $alias = Yii::$app->get('urlAlias', false);
        $meta = $alias ? trim($alias->meta_h1) : null;
        if (empty($meta)) {
            $desc = $this->desc;
            $meta = $desc ? Html::decode($desc->meta_h1) : '';
            if (empty($meta)) {
                $meta = $this->fullName;
            }
        }

        return City::replaceVars($meta);
    }

    /**
     * Заголовок страницы.
     *
     * @return string
     * @throws InvalidConfigException
     * @throws ExitException
     * @noinspection PhpUnused
     */
    public function getMetaTitle()
    {
        /** @var UrlAlias $alias */
        $alias = Yii::$app->get('urlAlias', false);

        /** @var ProdDesc $desc */
        $desc = $this->desc;

        // прописан в ЧПУ
        if ($alias !== null && ! empty($alias->meta_title)) {
            $meta = $alias->meta_title;
        }
        //
        // пропиан в товаре
        elseif ($desc !== null && ! empty($desc->meta_title)) {
            $meta = $desc->meta_title;
        }
        //
        // маркоразмер кабеля
        elseif ($this->categ->isMarka) {
            $meta = $this->name . ' купить оптом ${city.name3}, низкие цены на кабель/провод';
        }
        //
        // по-умолчанию
        else {
            $meta = $this->fullName . ' купить оптом ${city.name3}, цены';
        }

        return City::replaceVars($meta);
    }

    /**
     * Описание страницы.
     *
     * @return string
     * @throws InvalidConfigException
     * @throws ExitException
     * @noinspection PhpUnused
     */
    public function getMetaDesc()
    {
        /** @var UrlAlias $alias */
        $alias = Yii::$app->get('urlAlias', false);

        /** @var ProdDesc $desc */
        $desc = $this->desc;

        // прописан в ЧПУ
        if ($alias !== null && ! empty($alias->meta_desc)) {
            $meta = $alias->meta_desc;
        }
        //
        // пропиан в товаре
        elseif ($desc !== null && ! empty($desc->meta_description)) {
            $meta = $desc->meta_description;
        }
        //
        // маркоразмер кабеля
        elseif ($this->categ->isMarka) {
            $meta = '«РТК Новые технологии» ${city.name3} предлагает: ✅Купить ' .
                StringHelper::mb_lcfirst($this->fullName) . ' ✅Уточнить наличие ' . $this->name .
                ' и оптовые цены ✆${city.firstPhone}';
        }
        //
        // по-умолчанию
        else {
            $meta = '«РТК Новые технологии» ${city.name3} предлагает: ✅Купить ' . $this->name . ' ' .
                StringHelper::mb_lcfirst($this->categ->name) . ' ✅Уточнить наличие ' . $this->name .
                ' и оптовые цены ✆${city.firstPhone}';
        }

        return City::replaceVars($meta);
    }

    /**
     * Возвращает хлебные крошки.
     *
     * @return array
     * @noinspection PhpUnused
     */
    public function getBreadcrumbs()
    {
        $categ = $this->categ;
        $breadcrumbs = $categ !== null ? $categ->breadcrumbs : [];
        $breadcrumbs[] = [
            'text' => $this->name,
            'href' => $this->href
        ];

        return $breadcrumbs;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getUnits()
    {
        return $this->categ->units;
    }

    /**
     * Взвращает скорректированное значение цены в зависимоси от города.
     *
     * @return float
     * @noinspection PhpUnused
     */
    public function getPrice()
    {
        return empty($this->price) ? 0 : City::current()->adjustPrice($this->price);
    }

    /**
     * Фиг его знает какой image
     *
     * @return string
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function getYmlImageUrl()
    {
        if (! empty($this->image)) {
            return Registry::app()->load->model('tool/image')->resize($this->image, false, false);
        }

        if (! empty($this->categ->yml_image)) {
            return '/image/' . $this->categ->yml_image;
        }

        return '';
    }

    /**
     * Перекрестные ссылки на смежные товары.
     *
     * @param int $count
     * @return array [label => '...', url => '...']
     * @noinspection PhpUnused
     */
    public function getCrossSib(int $count = 6)
    {
        if (empty($this->product_id)) {
            return [];
        }

        $data = Yii::$app->cache->getOrSet([__METHOD__, (int)$this->product_id], function () use ($count) {
            return Prod::find()->alias('p')
                ->innerJoin(self::tableCateg() . ' p2c', 'p2c.[[product_id]]=p.[[product_id]]')
                ->joinWith('desc pd', false)
                ->select([
                    'name' => 'pd.[[name]]',
                    'product_id' => 'p.[[product_id]]'
                ])
                ->where([
                    'p.[[status]]' => 1,
                    'p2c.[[category_id]]' => $this->categoryId
                ])
                ->andWhere(['not', [
                    'p.[[product_id]]' => $this->product_id]
                ])
                ->orderBy(new Expression('rand()'))
                ->limit($count)
                ->indexBy('product_id')
                ->column();
        }, null, new TagDependency([
            'tags' => [self::class, ProdDesc::class]
        ]));

        $links = [];
        foreach ($data as $id => $name) {
            $links[] = [
                'label' => $name,
                'url' => Registry::app()->url->link('product/product', ['product_id' => $id])
            ];
        }

        return $links;
    }

    /**
     * Перекрестные ссылки на вышестоящие категориии.
     *
     * @param int $count
     * @return array [label => '...', url => '...']
     * @noinspection PhpUnused
     */
    public function getCrossUp(int $count = 6)
    {
        if (empty($this->product_id)) {
            return [];
        }

        $data = Yii::$app->cache->getOrSet([__METHOD__, (int)$this->product_id], function () use ($count) {
            $categ = $this->categ;
            if ($categ === null) {
                return [];
            }

            return Categ::find()->alias('c')
                ->joinWith('desc cd', false)
                ->select([
                    'name' => 'cd.[[name]]',
                    'category_id' => 'c.[[category_id]]'
                ])
                ->where([
                    'c.[[parent_id]]' => $categ->parent_id,
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
