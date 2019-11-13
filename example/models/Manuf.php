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
 * Производитель.
 *
 * @property int $manufacturer_id
 * @property string $name
 * @property string $image
 * @property int $sort_order
 */
class Manuf extends ActiveRecord
{
    /**
     * Имя таблицы.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{oc_manufacturer}}';
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

            ['image', 'trim'],
            ['image', 'string', 'max' => 255],

            ['sort_order', 'default', 'value' => 0],
            ['sort_order', 'integer'],
            ['sort_order', 'filter', 'filter' => 'intval']
        ];
    }
}
