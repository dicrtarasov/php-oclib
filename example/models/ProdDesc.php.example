<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */


namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use function trim;

/**
 * Описание товара.
 *
 * @property int $product_id
 * @property string $name короткое название
 * @property string $name_full полное название
 * @property string $description
 * @property string $short_description
 * @property string $primen
 * @property string $meta_title
 * @property string $meta_h1
 * @property string $meta_description
 *
 * // лишние
 * @property int $language_id [int(11) unsigned]
 * @property string $tag
 * @property string $meta_keyword [varchar(255)]
 *
 * // связи
 * @property-read Prod $prod
 *
 * @package app\models
 */
class ProdDesc extends ActiveRecord
{
    /**
     * Таблица.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{oc_product_description}}';
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'max' => 255],

            ['name_full', 'trim'],
            ['name_full', function($attribute) {
                $val = trim($this->{$attribute});
                if (empty($val)) {
                    $val = $this->name;
                }

                $this->{$attribute} = $val;
            }],
            ['name_full', 'string', 'max' => 255],

            ['primen', 'trim'],
            ['primen', 'string', 'max' => 2 ** 15]
        ];
    }

    /**
     * Связь с товаром.
     *
     * @return ActiveQuery
     * @noinspection PhpUnused
     */
    public function getProd()
    {
        return $this->hasOne(Prod::class, ['product_id' => 'product_id'])
            ->inverseOf('desc');
    }
}
