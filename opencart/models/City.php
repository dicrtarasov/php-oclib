<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\models;

use Html;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * Страны, города.
 *
 * @property-read int $id
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
 * @property string $firstPhone первый телефон из списка телефонов
 * @property bool $isDefault
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
     * Проверяет является ли город главным.
     *
     * @return bool
     */
    public function getIsDefault()
    {
        return $this->url === self::DOMAIN_DEFAULT;
    }

    /**
     * Возвращает текущий город.
     *
     * @return self
     * @throws \yii\base\InvalidConfigException
     */
    public static function current()
    {
        if (self::$_current === null) {
            self::$_current = self::findOne(['url' => $_SERVER['HTTP_HOST'] ?? '']);
            if (self::$_current === null) {
                self::$_current = self::findOne(['url' => self::DOMAIN_DEFAULT]);
                if (self::$_current === null) {
                    throw new InvalidConfigException('DOMAIN_DEFAULT: ' . self::DOMAIN_DEFAULT);
                }
            }
        }

        return self::$_current;
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
}
