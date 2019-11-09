<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */


namespace app\models;

use yii\db\ActiveRecord;

/**
 * Описание товара.
 *
 * @property int $product_id
 * @property string $name
 * @property string $description
 * @property string $short_description
 * @property bool $popular
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
 * @property-read \app\models\Prod $prod
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
     * Связь с товаром.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProd()
    {
        return $this->hasOne(Prod::class, ['product_id' => 'product_id'])
            ->inverseOf('desc');
    }
}
