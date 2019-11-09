<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

namespace app\models;

use Filter;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use function count;
use const SORT_ASC;
use const SORT_DESC;

/**
 * Фильр категорий.
 *
 * @property \yii\db\Query $query
 * @property \yii\data\ActiveDataProvider $provider
 *
 * @package app\models
 */
class CategFilter extends Model
{
    /** @var int|int[] */
    public $category_id;

    /** @var bool */
    public $recurse;

    /** @var int|int[] */
    public $parent_id;

    /** @var bool */
    public $status;

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['category_id', 'parent_id'], 'default', 'value' => []],
            [
                ['category_id', 'parent_id'],
                function($attribute) {
                    $this->{$attribute} = Filter::ids($this->{$attribute}) ?: null;
                }
            ],

            ['status', 'default'],
            ['status', 'boolean'],
            ['status', 'filter', 'filter' => 'boolval', 'skipOnEmpty' => true],

            ['recurse', 'default', 'value' => false],
            ['recurse', 'boolean'],
            ['recurse', 'filter', 'filter' => 'boolval']
        ];
    }

    /**
     * Взвращает запрос категорий.
     *
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getQuery()
    {
        $query = Categ::find()->alias('c')->select('c.*')->innerJoin(CategDesc::tableName() . ' cd',
                'cd.[[category_id]]=c.[[category_id]]')->leftJoin(Categ::tableCities() . ' c2c',
                'c2c.[[category_id]]=c.[[category_id]] and c2c.[[city_id]]=:city', [
                    ':city' => City::current()->id
                ]);

        if (! $this->validate()) {
            return $query->where('1=0');
        }

        if (isset($this->category_id) && count($this->category_id)) {
            $query->andWhere([
                'c.[[category_id]]' => $this->category_id
            ]);
        }

        if (! empty($this->parent_id)) {
            if ($this->recurse) {
                $query->andWhere([
                    'c.[[category_id]]' => (new Query())->select('cp.[[category_id]]')
                        ->from(Categ::tablePath() . ' cp')
                        ->where('cp.[[path_id]]!=cp.[[category_id]]')
                        ->andWhere(['cp.[[path_id]]' => $this->parent_id])
                ]);
            } else {
                $query->andWhere(['c.[[parent_id]]' => $this->parent_id]);
            }
        }

        if (isset($this->status)) {
            $query->andWhere(['c.[[status]]' => $this->status ? 1 : 0]);
        }

        return $query;
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
            'query' => $this->query,
            'sort' => [
                'attributes' => [
                    'sort_order' => [
                        'asc' => [
                            'isnull(c2c.[[sort_order]])' => SORT_ASC,
                            'c2c.[[sort_order]]' => SORT_ASC,
                            'c.[[sort_order]]' => SORT_ASC
                        ],
                        'desc' => [
                            'isnull(c2c.[[sort_order]])' => SORT_DESC,
                            'c2c.[[sort_order]]' => SORT_DESC,
                            'c.[[sort_order]]' => SORT_DESC
                        ]
                    ]
                ],
                'defaultOrder' => [
                    'sort_order' => SORT_ASC
                ]
            ],
        ], $config));
    }
}
