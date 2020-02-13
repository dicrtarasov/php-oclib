<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 05.12.19 04:05:30
 */

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use const SORT_ASC;
use const SORT_DESC;

/**
 * Фильтр городов.
 *
 * @property-read \yii\db\Query $query
 * @property-read \yii\data\ActiveDataProvider $provider
 * @package app\models
 */
class CityFilter extends Model
{
    /** @var string сортировка по имени */
    public const SORT_NAME = 'name';

    /** @var string поддомен */
    public $subdom;

    /** @var string название города */
    public $name;

    /**
     * @inheritDoc
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'subdom' => 'Поддомен',
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
            [['subdom', 'name'], 'trim'],
            [['subdom', 'name'], 'default']
        ];
    }

    /**
     * Провайдер данных.
     *
     * @param array $config
     * @return \yii\data\ActiveDataProvider
     */
    public function getProvider(array $config = [])
    {
        return new ActiveDataProvider(array_merge([
            'query' => $this->getQuery(),
            'sort' => [
                'attributes' => [
                    self::SORT_NAME => [
                        'asc' => ['name' => SORT_ASC],
                        'desc' => ['name' => SORT_DESC]
                    ]
                ]
            ]
        ], $config));
    }

    /**
     * Запрос.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuery()
    {
        $query = City::find();

        if (! ($this->validate())) {
            return $query->where('0');
        }

        $query->andFilterWhere(['subdom' => $this->subdom]);
        $query->andFilterWhere(['like', 'name', $this->name]);

        return $query;
    }

}
