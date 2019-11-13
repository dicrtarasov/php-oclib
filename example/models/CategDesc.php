<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */


namespace app\models;

use yii\db\ActiveRecord;

/**
 * Описание каегории
 *
 * @property int $category_id
 * @property string $name
 * @property string $singular единичное название товара в категории
 * @property string $description
 * @property string $description2
 * @property string $meta_h1
 * @property string $meta_title
 * @property string $meta_description
 * @property bool $marka
 * @property string $microrazm микроразметка html-текст
 * @property string $primen применение
 * @property string $rightcol правая колонка
 * @property string $catmenimg
 *
 * // relations
 * @property-read \app\models\Categ $categ
 *
 * // не используемые
 * @property int $language_id [int(11) unsigned]
 * @property int $subcat_count [int(255)]
 * @property string $h1_shab [varchar(80)]
 * @property string $h1_shab2 [varchar(80)]
 * @property string $meta_keyword [varchar(255)]
 * @property int $how_to_order [int(1)]
 * @property string $cat_keys [varchar(150)]
 * @property bool $template_text [tinyint(4)]
 *
 * @package app\models
 */
class CategDesc extends ActiveRecord
{
    /**
     * Описание категории.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{oc_category_description}}';
    }

    /**
     * Возвращает категорию.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCateg()
    {
        return $this->hasOne(Categ::class, ['category_id' => 'category_id'])
            ->inverseOf('desc');
    }



}
