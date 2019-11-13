<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\models;

use Country;
use Html;
use LogicException;
use yii\db\ActiveRecord;
use function count;
use function is_array;

/**
 * Страны, города.
 *
 * @property-read int $id
 * @property int $country_id
 * @property string $url домен
 * @property string $gerb путь к каринке герба
 * @property string $name1 название в имениельном падеже (Город)
 * @property string $name2 доставка (по Городу)
 * @property string $name3 где (в Городе)
 * @property string $name4 доставка (в Город)
 * @property string $address полный адрес
 * @property string $phone html-телефоны
 * @property int $metrika номер счетчика метрики
 * @property string $google идентификатор счетчика google
 * @property string $map скрипт карты
 * @property string $main_text основной html-текст
 * @property string $name99 null
 * @property string $name98 null
 * @property string $coord json [lat, lon]
 *
 * @property-read  string $firstPhone первый телефон из списка телефонов
 * @property-read  bool $isDefault
 * @property-read  Country $country
 */
class City extends ActiveRecord
{
    /** @var string домен города по-умолчанию */
    public const DOMAIN_DEFAULT = 'rtk-nt.ru';

    /** @var self текущий город */
    private static $_current;

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
     * @throws \yii\web\NotFoundHttpException
     */
    public function rules()
    {
        return [
            ['country_id', 'default', 'value' => Country::default()->country_id],
            ['country_id', 'integer'],
            ['country_id', 'min' => 1],

            ['url', 'trim'],
            ['url', 'required'],
            ['url', 'string', 'max' => 64],
            ['url', 'url'],
            ['url', 'unique'],

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
                'coord',
                function($attribute) {
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
     * Выбирает первй телефон из списка телефонов.
     *
     * @return string
     */
    public function getFirstPhone()
    {
        $phones = preg_split('~<br\s*/?>|[,\v\r\n]+~uim', $this->phone, - 1, PREG_SPLIT_NO_EMPTY);
        foreach ($phones as $phone) {
            $phone = trim(Html::toText($phone));
            if ($phone !== '') {
                return $phone;
            }
        }

        return '';
    }

    /**
     * Подстановка переменных в тексте.
     *
     * @param string|null $text
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function replaceVars(string $text = null)
    {
        if ($text === null) {
            return '';
        }

        $current = self::current();

        $srch = ['%city%', '%around_city%', '%in_city%'];
        $rpls = [$current->name1, $current->name2, $current->name3];

        // замена телефона
        $phone = $current->firstPhone;
        if ($phone !== '') {
            $srch[] = '8-800-550-89-52';
            $rpls[] = $phone;
        }

        return str_replace($srch, $rpls, $text);
    }

    /**
     * Возвращает текущий город.
     *
     * @return self
     */
    public static function current()
    {
        if (! isset(self::$_current)) {
            self::$_current = self::findOne(['url' => $_SERVER['HTTP_HOST'] ?? '']);
            if (empty(self::$_current)) {
                self::$_current = self::default();
            }
        }

        return self::$_current;
    }

    /** @var self */
    private static $_default;

    /**
     * Город по-умолчанию.
     *
     * @return string
     */
    public static function default()
    {
        if (! isset(self::$_default)) {
            self::$_default = self::findOne(['url' => self::DOMAIN_DEFAULT]);
            if (empty(self::$_default)) {
                throw new LogicException('Город по-умолчанию не найден: ' . self::DOMAIN_DEFAULT);
            }
        }

        return self::$_default;
    }

    /**
     * Проверяет является ли город главным.
     *
     * @return bool
     */
    public function getIsDefault()
    {
        return $this->url === self::DOMAIN_DEFAULT;
    }

    /**
     * Возвращает запрос страны города.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Country::class, ['country_id' => 'country_id']);
    }
}
