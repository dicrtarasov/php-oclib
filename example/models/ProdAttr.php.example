<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace app\models;

use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class ProdAttr
 *
 * @property int $product_id [int(11) unsigned]
 * @property int $attribute_id [int(11) unsigned]
 * @property string $text
 *
 * @property-read Prod $prod
 * @property-read Attr $attr
 */
class ProdAttr extends ActiveRecord
{
    /**
     * Таблица.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%product_attribute}}';
    }

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['product_id', 'attribute_id'], 'required'],
            [['product_id', 'attribute_id'], 'integer', 'min' => 1],
            [['product_id', 'attribute_id'], 'filter', 'filter' => 'intval'],

            ['text', 'trim'],
            ['text', 'required'],
            ['text', 'string', 'max' => 128]
        ];
    }

    /**
     * Возвращает запрос товара.
     *
     * @return ActiveQuery
     */
    public function getProd()
    {
        return $this->hasOne(Prod::class, ['product_id' => 'product_id'])
            ->cache(true, new TagDependency([
                'tags' => [self::class]
            ]));
    }

    /**
     * Запрос характеристики.
     *
     * @return ActiveQuery
     */
    public function getAttr()
    {
        return $this->hasOne(Attr::class, ['attribute_id' => 'attribute_id'])
            ->cache(true, new TagDependency([
                'tags' => [self::class]
            ]));
    }
}
