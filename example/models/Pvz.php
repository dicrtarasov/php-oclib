<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 06.12.19 19:20:37
 */

declare(strict_types = 1);
namespace app\models;

use yii\db\ActiveRecord;
use function count;

/**
 * Пункт самовывоза.
 *
 * @property-read int $id
 * @property int $delivery_id
 * @property int $city_id
 * @property string $address
 * @property float[] $geo
 *
 * Relations
 *
 * @property-read \app\models\Delivery $delivery служба доставки
 * @property-read \app\models\City $city город
 *
 * @package app\models
 */
class Pvz extends ActiveRecord
{
    /**
     * @inheritDoc
     * @return string
     */
    public static function tableName()
    {
        return '{{%pvz}}';
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'delivery_id' => 'Служба доставки',
            'city_id' => 'Город',
            'address' => 'Адрес',
            'geo' => 'Координаты'
        ];
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function rules()
    {
        return [
            [['delivery_id', 'city_id'], 'required'],
            [['delivery_id', 'city_id'], 'integer', 'min' => 1],
            [['delivery_id', 'city_id'], 'filter', 'filter' => 'intval'],

            ['address', 'trim'],
            ['address', 'required'],
            ['address', 'string', 'max' => 128],
            ['address', 'unique', 'targetAttribute' => ['delivery_id', 'city_id', 'address']],

            [
                'geo',
                function(string $attribute) {
                    $val = (array)($this->{$attribute} ?: []);
                    if (! empty($val)) {
                        if (count($val) !== 2) {
                            $this->addError($attribute, 'Некорректный формат кординат');
                        } else {
                            for ($i = 0; $i < 2; $i ++) {
                                $val[$i] = (float)$val[$i];
                                if ($val[$i] <= 0) {
                                    $this->addError('Некоррекный формат координат');
                                    break;
                                }
                            }
                        }
                    }

                    $this->{$attribute} = $val;
                }
            ],
        ];
    }

    /**
     * Запрос службы доставки.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDelivery()
    {
        return $this->hasOne(Delivery::class, ['id' => 'delivery_id']);
    }

    /**
     * Запрос города.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'city_id']);
    }
}
