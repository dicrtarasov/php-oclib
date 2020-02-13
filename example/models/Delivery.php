<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 06.12.19 19:16:27
 */

declare(strict_types = 1);
namespace app\models;

use dicr\helper\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * Служба доставки.
 *
 * @property-read int $id
 * @property string $name
 *
 * Relations
 *
 * @property \app\models\Pvz $pvzs пункты самовывоза
 */
class Delivery extends ActiveRecord
{
    /**
     * @inheritDoc
     * @return string
     */
    public static function tableName()
    {
        return '{{%delivery}}';
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Название'
        ];
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function rules()
    {
        return [
            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'max' => 40],
            ['name', 'unique']
        ];
    }

    /**
     * Запрос пунктов самовывоза.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPvzs()
    {
        return $this->hasMany(Pvz::class, ['delivery_id' => 'id'])->inverseOf('delivery')->indexBy('id');
    }

    /**
     * Устанавливает пункты самовывоза.
     *
     * @param \app\models\Pvz[] $pvzs
     * @return string[] errors
     */
    public function setPvzs(array $pvzs)
    {
        /** @var \app\models\Pvz[] $pvzs */
        $pvzs = ArrayHelper::index($pvzs, 'id');
        $errors = [];

        Pvz::deleteAll(['delivery_id' => $this->id]);

        // сохраняем pvz
        foreach ($pvzs as $pvz) {
            $pvz->delivery_id = $this->id;
            if ($pvz->save(true) === false) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $errors = array_merge($errors, $pvz->firstErrors);
            }
        }

        $this->populateRelation('pvzs', $pvzs);

        return $errors;
    }
}
