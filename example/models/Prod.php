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
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\Query;
use function count;
use function in_array;

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
 *
 * // relations
 * @property-read \app\models\ProdDesc $desc
 * @property-read \app\models\Categ $categ
 * @property-read \app\models\Manuf $manuf
 * @property-read \app\models\ProdAttr[] $attrs
 *
 * // связанные из ProdDesc
 *
 * @property-read string $name
 * @property-read string $description
 * @property-read string $shortDescription
 * @property-read bool $popular
 * @property-read string $primen
 * @property-read string $metaH1
 * @property-read string $metaTitle
 * @property-read string $metaDescription
 *
 * // виртуальные
 *
 * @property-read array $breadcrumbs
 * @property-read $imageRecurse
 * @property-read $imageUrl
 * @property-read $url
 * @property-read string $fullName полное имя с присавкой singular из каегории
 * @property-read string $units единицы измерения
 */
class Prod extends ActiveRecord
{
    /** @var float коэффициент цены для 100 метров кабеля */
    public const DISCOUNT_100 = 0.99;

    /** @var float коэффициент цены для 100 метров кабеля */
    public const DISCOUNT_1000 = 0.97;

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
     * Связь с описанием.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDesc()
    {
        return $this->hasOne(ProdDesc::class, ['product_id' => 'product_id'])->inverseOf('prod');
    }

    /**
     * Связь с категорией.
     *
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
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
     * @return \yii\db\ActiveQuery
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
     * @return \yii\db\ActiveQuery
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
     */
    public function getImageRecurse()
    {
        if ($this->image !== '') {
            return $this->image;
        }

        if (! isset($this->_imageRecurse)) {
            $categ = $this->categ;
            if (empty($categ)) {
                $this->_imageRecurse = '';
            } elseif (in_array($categ->topCategId, [Categ::ID_IMPORTCABLE, Categ::ID_CABLEPROV], true)) {
                $this->_imageRecurse = 'kabs-gl.svg';
            } else {
                $this->_imageRecurse = $categ->imageRecurse;
            }
        }

        return $this->_imageRecurse;
    }

    /**
     * Возвращает URL каринки.
     *
     * @param array $options
     * - bool $recurse рекурсивный поиск картинки
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
     * Возвращает URL превью.
     *
     * @param int $width
     * @param int $height
     * @param array|null $options
     * @return string url превью
     * @throws \yii\base\Exception
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

    /** @var string */
    private $_url;

    /**
     * Возвращает URL товара.
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
            return Registry::app()->url->link('product/product', array_merge($params, [
                'product_id' => $this->product_id
            ]));
        }

        if (! isset($this->_url)) {
            $this->_url = Registry::app()->url->link('product/product', [
                'product_id' => $this->product_id
            ]);
        }

        return $this->_url;
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

    /** @var string полное название товара */
    private $_fullName;

    /**
     * Полное название товара, включая singular из названия категории.
     *
     * @return string
     */
    public function getFullName()
    {
        if (! isset($this->_fullName)) {
            $singular = '';

            $categ = $this->categ;
            if ($categ !== null) {
                $singular = $categ->singular;
            }

            $this->_fullName = empty($singular) ? $this->name : $singular . ' ' . $this->name;
        }

        return $this->_fullName;
    }

    /**
     * Описание товара.
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
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

    /**
     * Короткое описание.
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getShortDescription()
    {
        $desc = $this->desc;
        if (! isset($desc)) {
            return '';
        }

        $text = trim(Html::decode($desc->short_description));
        if (trim(Html::toText($text)) === '') {
            return '';
        }

        return City::replaceVars($text);
    }

    /**
     * Популярный товар.
     *
     * @return bool
     */
    public function getPopular()
    {
        $desc = $this->desc;
        return isset($desc) ? (bool)$desc->popular : false;
    }

    /**
     * Применение товара.
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
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
     * @throws \yii\base\InvalidConfigException
     */
    public function getMetaH1()
    {
        $desc = $this->desc;
        if (empty($desc)) {
            return '';
        }

        $text = Html::decode($desc->meta_h1);
        if (trim(Html::toText($text)) === '') {
            return '';
        }

        return City::replaceVars($text);
    }

    /**
     * Заголовок страницы.
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getMetaTitle()
    {
        $desc = $this->desc;
        if (! isset($desc)) {
            return '';
        }

        $text = Html::decode($desc->meta_title);
        if (trim(Html::toText($text)) === '') {
            return '';
        }

        return City::replaceVars($text);
    }

    /**
     * Описание страницы.
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getMetaDescription()
    {
        $desc = $this->desc;
        if (! isset($desc)) {
            return '';
        }

        $text = Html::decode($desc->meta_description);
        if (trim(Html::toText($text)) === '') {
            return '';
        }

        return City::replaceVars($text);
    }

    /**
     * Возвращает хлебные крошки.
     *
     * @return array
     */
    public function getBreadcrumbs()
    {
        $categ = $this->categ;
        $breadcrumbs = ! empty($categ) ? $categ->breadcrumbs : [];
        $breadcrumbs[] = [
            'text' => $this->name,
            'href' => $this->url
        ];

        return $breadcrumbs;
    }

    /**
     * @return string
     */
    public function getUnits()
    {
        return $this->categ->units;
    }
}
