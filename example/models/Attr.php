<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\models;

use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Характеристика.
 *
 * @property int $attribute_id
 * @property string $name
 * @property int $type
 * @property array $values
 */
class Attr extends ActiveRecord
{
    /** @var int */
    public const TYPE_STRING = 0;

    /** @var int */
    public const TYPE_FLAG = 1;

    /** @var int */
    public const TYPE_NUMBER = 2;

    /** @var string[] типы характеристик */
    public const TYPES = [
        self::TYPE_STRING => 'строка',
        self::TYPE_FLAG => 'флаг',
        self::TYPE_NUMBER => 'число'
    ];

    /** @var int Сечение провода */
    public const ID_SECH = 41627;

    /** @var int Количество жил */
    public const ID_ZHIL = 41628;

    /**
     * Таблица.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{oc_attribute}}';
    }

    /**
     * Таблица характерисик товаров.
     *
     * @return string
     */
    public static function tableProds()
    {
        return '{{oc_product_attribute}}';
    }

    /**
     * Правила.
     *
     * @return array[]
     */
    public function rules()
    {
        return [
            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'max' => 128],
            ['name', 'unique'],

            ['type', 'default', 'value' => self::TYPE_STRING],
            ['type', 'in', 'range' => array_keys(self::TYPES)],
            ['type', 'filter', 'filter' => 'intval']
        ];
    }

    /** @var string[] */
    private $_values;

    /**
     * Устанавливает набр значений.
     *
     * @param array $values
     */
    public function setValues(array $values)
    {
        $this->_values = $values;
    }

    /**
     * Возвращает набор значений
     *
     * @return array
     */
    public function getValues()
    {
        if (!isset($this->_values)) {
            $query = (new Query())->select('pa.[[text]]')
                ->distinct(true)
                ->from(Prod::tableAttr() . ' pa')
                ->where(['pa.[[attribute_id]]' => $this->attribute_id])
                ->andWhere('pa.[[text]] != ""');

            switch ($this->type) {
                case self::TYPE_FLAG:
                    $query->orderBy('cast(pa.[[text]] as unsigned), pa.[[text]]');
                    break;

                case self::TYPE_NUMBER:
                    $query->orderBy('cast(pa.[[text]] as decimal(10,3), pa.[[text]]');
                    break;

                default:
                    $query->orderBy('pa.[[text]]');
            }

            $this->_values = $query->cache(true, new TagDependency([
                'tags' => [self::class]
            ]))->column();
        }

        return $this->_values;
    }
}
