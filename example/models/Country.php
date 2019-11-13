<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

use app\models\City;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * Страна.
 *
 * @property int $country_id [int(11)]
 * @property string $name [varchar(128)]
 * @property string $iso_code_2 [varchar(2)]
 * @property string $iso_code_3 [varchar(3)]
 * @property string $address_format
 * @property bool $postcode_required [tinyint(1)]
 * @property bool $status [tinyint(1)]
 *
 * @property-read \app\models\City[] $cities
 */
class Country extends ActiveRecord
{
    /** @var string iso страны по-умолчанию */
    public const ISO_DEFAULT = 'RU';

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
            ['name', 'string', 'max' => 128],
            ['name', 'unique'],

            ['iso_code_2', 'trim'],
            ['iso_code_2', 'required'],
            ['iso_code_2', 'string', 'length' => 2],
            ['iso_code_2', 'unique'],

            ['iso_code_3', 'trim'],
            ['iso_code_3', 'required'],
            ['iso_code_3', 'string', 'length' => 3],

            ['address_format', 'trim'],
            ['address_format', 'string', 'max' => 256],

            ['postcode_required', 'default', 'value' => true],
            ['postcode_required', 'boolean'],
            ['postcode_required', 'filter', 'filter' => 'boolval'],

            ['status', 'default', 'value' => 1],
            ['status', 'boolean'],
            ['status', 'filter', 'filter' => 'intval']
        ];
    }

    /** @var self  */
    private static $_default;

    /**
     * Возвращает страну по-умолчанию.
     *
     * @return \Country|null
     * @throws \yii\web\NotFoundHttpException
     */
    public static function default()
    {
        if (!isset($self::$_default)) {
            self::$_default = self::findOne(['iso_code_2' => self::ISO_DEFAULT, 'status' => 1]);
            if (empty(self::$_default)) {
                throw new NotFoundHttpException('Не найдена страна по-умолчанию: ' . self::ISO_DEFAULT);
            }
        }

        return self::$_default;
    }

    /**
     * Возвращает текущую страну.
     *
     * @return self
     * @throws \yii\base\InvalidConfigException
     */
    public static function current()
    {
        return City::current()->country;
    }

    /**
     * Города страны.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCities()
    {
        return $this->hasMany(City::class, ['country_id' => 'country_id'])
            ->inverseOf('country')
            ->indexBy('id');
    }
}
