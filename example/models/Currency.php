<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Валюта.
 *
 * @property int $currency_id [int(11)]
 * @property string $title название валюты
 * @property string $code 3-х буквенный код
 * @property string $symbol_left символ валюты до числа
 * @property string $symbol_right символ валюты после числа
 * @property string $decimal_place кол-во десяичных знаков (2)
 * @property float $value обратный курс валюты (отношение цены к валюте)
 * @property bool $status вкл./откл.
 * @property string $date_modified дата изменения записи
 */
class Currency extends ActiveRecord
{
    /**
     * Таблица.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%currency}}';
    }

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['title', 'trim'],
            ['title', 'required'],
            ['title', 'string', 'max' => 24],
            ['title', 'unique'],

            ['code', 'trim'],
            ['code', 'required'],
            ['code', 'string', 'max' => 3],
            ['code', 'unique'],

            [['symbol_left', 'symbol_right'], 'trim'],
            [['symbol_left', 'symbol_right'], 'string', 'max' => 8],

            ['decimal_place', 'default', 'value' => 2],
            ['decimal_place', 'integer', 'min' => 0],
            ['decimal_place', 'filter', 'filter' => 'intval'],

            ['value', 'default', 'value' => 1],
            ['value', 'number', 'min' => 0.00000001],
            ['value', 'filter', 'filter' => 'floatval'],

            ['status', 'default', 'value' => true],
            ['status', 'boolean'],
            ['status', 'filter', 'filter' => 'boolval'],
        ];
    }

    /**
     * Форматирует значение валюты.
     *
     * @param float $value
     * @return string
     */
    public function format(float $value)
    {
        // рассчитываем по текущему курсу
        if (! empty($this->value)) {
            $value *= $this->value;
        }

        $string = '';

        if ((string)$this->symbol_left !== '') {
            $string .= $this->symbol_left;
        }

        $string .= number_format($value, (int)$this->decimal_place, '.', ' ');

        if ((string)$this->symbol_right !== '') {
            $string .= $this->symbol_right;
        }

        return $string;
    }

    /**
     * Возвращает текущую валюту.
     *
     * @return \app\models\Currency
     */
    public static function current()
    {
        static $current;

        if (! isset($current)) {
            $current = Country::current()->currency;
        }

        return $current;
    }
}
