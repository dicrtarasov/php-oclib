<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Фильтр характеристик.
 *
 * @package app\models
 * @property-read \yii\db\ActiveQuery $query
 * @property-read \yii\data\ActiveDataProvider $provider
 */
class AttrFilter extends Model
{
    /** @var string */
    public $name;

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['name', 'trim'],
            ['name', 'default'],
        ];
    }

    /**
     * Возвращает провайдер данных.
     *
     * @param array $config
     * @return \yii\data\ActiveDataProvider
     */
    public function getProvider(array $config = [])
    {
        return new ActiveDataProvider(array_merge([
            'query' => $this->getQuery(),
            'sort' => [
                'route' => \Yii::$app->requestedRoute,
                'attributes' => [
                    'name' => [
                        'asc' => ['a.[[name]]' => SORT_ASC],
                        'desc' => ['a.[[name]]' => SORT_DESC],
                        'label' => 'Название'
                    ],
                    'type' => [
                        'asc' => ['a.[[type]]' => SORT_ASC],
                        'desc' => ['a.[[type]]' => SORT_DESC],
                        'label' => 'Тип'
                    ]
                ],
                'defaultOrder' => [
                    'name' => SORT_ASC,
                ]
            ],
            'pagination' => [
                'route' => \Yii::$app->requestedRoute
            ]
        ], $config));
    }

    /**
     * Возвращает запрос характеристик.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuery()
    {
        $query = Attr::find()->alias('a');

        if (! $this->validate()) {
            return $query->where('1=0');
        }

        $query->andFilterWhere(['like', 'a.[[name]]', $this->name]);

        return $query;
    }
}
