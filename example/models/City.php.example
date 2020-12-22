<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 11.02.20 11:46:23
 */

declare(strict_types = 1);

namespace app\models;

use LogicException;
use Throwable;
use Yii;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use function array_values;
use function count;
use function is_array;
use function preg_replace_callback;
use function reset;
use function str_replace;
use function trim;
use const DOMAIN;
use const SCHEME;
use const YII_ENV_PROD;

/**
 * Города.
 *
 * @property-read int $id
 * @property int $country_id
 * @property string $subdom поддомен города (домен в стране)
 * @property string $gerb путь к картинке герба
 * @property string $name1 название в именительном падеже (Город)
 * @property string $name2 доставка (по Городу)
 * @property string $name3 где (в Городе)
 * @property string $name4 доставка (в Город)
 * @property string $name98 null
 * @property string $name99 null
 * @property string $address полный адрес
 * @property string $phone html-телефоны
 * @property int $metrika номер счетчика метрики
 * @property string $google идентификатор счетчика google
 * @property string $map скрипт карты
 * @property string $main_text html-текст на главной странице
 * @property string $coord json [lat, lon]
 *
 * Relations
 *
 * @property-read Country $country
 * @property-read Pvz[] $pvzs пункты самовывоза
 * @property-read Delivery $deliverys службы доставки
 *
 * Calculated
 *
 * @property-read string[] $allPhones телефон города (если есть) вместе с общим телефоном.
 * @property-read string $firstPhone первый телефон (телефон города или общий)
 * @property-read bool $isPerm город Пермь
 * @property-read bool $isDefault город по-умолчанию
 * @property-read bool $isRu является Россией
 * @property-read bool $isKz является Казахстаном
 *
 * @property string $url [varchar(64)]  удалить
 * @noinspection LongInheritanceChainInspection
 */
class City extends ActiveRecord
{
    /** @var string домен города по-умолчанию */
    public const DOMAIN_DEFAULT = DOMAIN;

    /** @var string общий многоканальный телефон */
    public const PHONE_COMMON = '8-800-550-89-52';

    /**
     * Таблица БД.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{cities}}';
    }

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['country_id', 'default', 'value' => self::default()->country_id],
            ['country_id', 'integer'],
            ['country_id', 'min' => 1],
            ['country_id', 'filter', 'filter' => 'intval'],

            ['subdom', 'trim'],
            ['subdom', 'required'],
            ['subdom', 'string', 'max' => 20],
            ['subdom', 'unique', 'targetAttribute' => ['subdom', 'country_id']],

            ['gerb', 'trim'],
            ['gerb', 'string', 'max' => 128],

            [['name1', 'name2', 'name3', 'name4'], 'trim'],
            [['name1', 'name2', 'name3', 'name4'], 'required'],
            [['name1', 'name2', 'name3', 'name4'], 'string', 'max' => 32],
            ['name1', 'unique'],

            [['name98', 'name99'], 'trim'],
            [['name98', 'name99'], 'string', 'max' => 32],

            ['address', 'trim'],
            ['address', 'string', 'max' => 128],

            ['phone', 'trim'],
            ['phone', 'string', 'max' => 64],

            ['metrika', 'default', 'value' => 0],
            ['metrika', 'integer', 'min' => 1],
            ['metrika', 'filter', 'filter' => 'intval'],

            ['google', 'trim'],
            ['google', 'string', 'max' => 24],

            ['map', 'trim'],
            ['map', 'string', 'max' => 1024],

            ['main_text', 'trim'],
            ['main_text', 'string', 'max' => 64000],

            ['coord', 'default', 'value' => []],
            [
                'coord', function($attribute) {
                $val = array_values($this->{$attribute} ?: []);
                if (! is_array($val) || count($val) !== 2) {
                    return $this->addError($attribute, 'Должен быть массив 2-х координат');
                }

                foreach ($val as &$v) {
                    $v = (float)$v;
                    if ($v <= 0) {
                        return $this->addError($attribute, 'некоррекное значение координаты');
                    }
                }

                unset($v);
                $this->{$attribute} = $val;

                return true;
            }
            ]
        ];
    }

    /**
     * Город по-умолчанию.
     *
     * @return self
     */
    public static function default()
    {
        static $default;

        if (! isset($default)) {
            $default = self::findByHostname(self::DOMAIN_DEFAULT);
            if ($default === null) {
                throw new LogicException('Город по-умолчанию не найден: ' . self::DOMAIN_DEFAULT);
            }
        }

        return $default;
    }

    /**
     * Возвращает город по полному имени домена.
     *
     * @param string $hostname если не задан, то берется $_SERVER[HTTP_HOST]
     * @return self|null
     */
    public static function findByHostname(string $hostname = '')
    {
        if (empty($hostname)) {
            $hostname = (string)($_SERVER['HTTP_HOST'] ?? '');
        }

        if (empty($hostname)) {
            return null;
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $hostname === '' ? null : self::find()
            ->alias('s')
            ->joinWith('country c', false)
            ->where([
                'if(char_length(s.[[subdom]]) < 1, c.[[domain]], concat_ws(".", s.[[subdom]], c.[[domain]]))' => $hostname
            ])->cache(true, new TagDependency([
                'tags' => [self::class, Country::class]
            ]))
            ->limit(1)
            ->one();
    }

    /**
     * Все телефоны.
     *
     * @return string[] телефон города (если есть) и общие телефоны.
     */
    public function getAllPhones()
    {
        $phones = [];
        if (! empty($this->phone)) {
            $phones[] = $this->phone;
        }

        $phones[] = self::PHONE_COMMON;

        return array_unique($phones);
    }

    /**
     * Возвращает первый телефон.
     *
     * @return string
     */
    public function getFirstPhone()
    {
        $allPhones = $this->allPhones;

        return reset($allPhones);
    }

    /**
     * Подстановка переменных в тексте.
     *
     * @param string|null $text
     * @return string
     */
    public static function replaceVars(string $text = null)
    {
        if ($text === null) {
            return '';
        }

        $current = self::current();

        $srch = ['%city%', '%around_city%', '%in_city%', self::PHONE_COMMON];
        $rpls = [$current->name1, $current->name2, $current->name3, $current->firstPhone];

        $text = str_replace($srch, $rpls, $text);

        return trim(
            preg_replace_callback(
                '~\${city\.([^}]+)}~uim',
                static function($matches) use ($current) {
                    return $current->{$matches[1]} ?: '';
                },
                $text
            )
        );
    }

    /**
     * Возвращает текущий город.
     *
     * @return self
     */
    public static function current()
    {
        /** @var self текущий город */
        static $current;

        if (! isset($current)) {
            $current = self::findByHostname();
            if ($current === null) {
                $current = self::default();
                if (YII_ENV_PROD) {
                    try {
                        Yii::$app->end(0, Yii::$app->response->redirect(SCHEME . '://' . self::DOMAIN_DEFAULT, 301));
                    } catch (Throwable $ex) {
                        Yii::error($ex, __METHOD__);
                        exit;
                    }
                }
            }
        }

        return $current;
    }

    /**
     * Проверяет является ли городом по-умолчанию.
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsDefault()
    {
        return $this->id === self::default()->id;
    }

    /**
     * Проверяет является ли городом по-умолчанию.
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsPerm()
    {
        return $this->isDefault;
    }

    /**
     * Возвращает запрос страны города.
     *
     * @return ActiveQuery
     * @noinspection PhpUnused
     */
    public function getCountry()
    {
        return $this->hasOne(Country::class, ['country_id' => 'country_id']);
    }

    /**
     * Является Россией.
     *
     * @return bool
     */
    public function getIsRu()
    {
        $country = $this->country;

        return $country !== null ? $country->isRu : false;
    }

    /**
     * Является Казахстаном.
     *
     * @return bool
     */
    public function getIsKz()
    {
        $country = $this->country;

        return $country !== null ? $country->isKz : false;
    }

    /**
     * Слегка дифференцирует цены на небольшое значени в разных городах.
     * В зависимости от id города добавляет маленькое значение.
     *
     * @param float $price
     * @return float
     */
    public function adjustPrice(float $price)
    {
        return $price + $this->id * ($this->isDefault ? 0 : 0.05);
    }

    /**
     * Запрос пунктов самовывоза
     *
     * @return ActiveQuery
     * @noinspection PhpUnused
     */
    public function getPvzs()
    {
        return $this->hasMany(Pvz::class, ['city_id' => 'id'])->inverseOf('city')->indexBy('id');
    }

    /**
     * Запрос служб доставки.
     *
     * @return ActiveQuery
     * @noinspection PhpUnused
     */
    public function getDeliverys()
    {
        return $this->hasMany(Delivery::class, ['id' => 'delivery_id'])->via('pvzs');
    }
}
