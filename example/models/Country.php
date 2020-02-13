<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 05.12.19 00:48:00
 */

declare(strict_types = 1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Страна.
 *
 * @property int $country_id [int(11)]
 * @property string $name [varchar(128)]
 * @property string $domain домен сайта для страны
 * @property string $iso_code_2 код страны
 * @property int $currency_id валюта
 * @property bool $status [tinyint(1)]
 *
 * Relations
 *
 * @property-read City[] $cities
 * @property-read Currency $currency
 *
 * Virtual
 *
 * @property-read string $code синоним для iso_code_2
 * @property-read bool $isRu является Россией
 * @property-read bool $isKz является казахстаном
 */
class Country extends ActiveRecord
{
    /** @var string код России */
    public const CODE_RU = 'RU';

    /** @var string код Белоруссии */
    public const CODE_BY = 'BY';

    /** @var string код Казахстана */
    public const CODE_KZ = 'KZ';

    /**
     * Таблица.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%country}}';
    }

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'max' => 64],
            ['name', 'unique'],

            ['domain', 'trim'],
            ['domain', 'required'],
            ['domain', 'string', 'max' => 32],

            ['iso_code_2', 'trim'],
            ['iso_code_2', 'required'],
            ['iso_code_2', 'string', 'length' => 2],
            ['iso_code_2', 'unique'],

            ['currency_id', 'default'],
            ['currency_id', 'integer', 'min' => 1],
            ['currency_id', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            ['status', 'default', 'value' => 1],
            ['status', 'boolean'],
            ['status', 'filter', 'filter' => 'intval']
        ];
    }

    /**
     * Возвращает страну по-умолчанию.
     *
     * @return self|null
     */
    public static function default()
    {
        return City::default()->country;
    }

    /**
     * Возвращает текущую страну.
     *
     * @return self
     */
    public static function current()
    {
        return City::current()->country;
    }

    /**
     * Города страны.
     *
     * @return ActiveQuery
     */
    public function getCities()
    {
        return $this->hasMany(City::class, ['country_id' => 'country_id'])->inverseOf('country')->indexBy('id');
    }

    /**
     * Возвращает запрос валюты.
     *
     * @return ActiveQuery
     */
    public function getCurrency()
    {
        return $this->hasOne(Currency::class, ['currency_id' => 'currency_id']);
    }

    /**
     * Код страны.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->iso_code_2;
    }

    /**
     * Является Россией.
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsRu()
    {
        return $this->code === self::CODE_RU;
    }

    /**
     * Является Казахстаном.
     *
     * @return bool
     */
    public function getIskz()
    {
        return $this->code === self::CODE_KZ;
    }
}
