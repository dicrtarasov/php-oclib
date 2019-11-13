<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

namespace app\models;

use app\components\Sort;
use dicr\oclib\Filter;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use const SORT_ASC;
use const SORT_DESC;

/**
 * Фильтр производителей.
 *
 * @property-read \yii\db\ActiveQuery $query
 * @property-read \yii\data\ActiveDataProvider $provider
 *
 * @package app\models
 */
class ManufFilter extends Model
{
    /** @var int|int[] */
    public $category_id;

    /** @var boolean */
    public $recurse;

    /** @var boolean */
    public $status;

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['category_id', 'default'],
            [
                'category_id',
                function() {
                    $this->category_id = Filter::ids($this->category_id) ?: null;
                }
            ],

            ['recurse', 'default', 'value' => false],
            ['recurse', 'boolean'],
            ['recurse', 'filter', 'filter' => 'boolval'],

            ['status', 'default'],
            ['status', 'boolean'],
            ['status', 'filter', 'filter' => 'boolval', 'skipOnEmpty' => true]
        ];
    }

    /**
     * Возвращает запрос.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuery()
    {
        $query = Manuf::find()->alias('m');

        if (! $this->validate()) {
            return $query->where('1=0');
        }

        if (! empty($this->category_id)) {
            $query->andWhere([
                'm.[[manufacturer_id]]' => (new Query())->select('p.[[manufacturer_id]]')->from(Prod::tableName() .
                                                                                                ' p')->where([
                        'p.[[product_id]]' => (new Query())->select('p2c.[[product_id]]')->from(Prod::tableCateg() .
                                                                                                ' p2c')->where([
                                'p2c.[[category_id]]' => $this->recurse ? (new Query())->select('cp.[[category_id]]')
                                    ->from(Categ::tablePath() . ' cp')
                                    ->where(['cp.[[path_id]]' => $this->category_id]) : $this->category_id
                            ])
                    ])
            ]);
        }

        if (isset($this->status)) {
            $query->andWhere('p.[[status]]=1')->innerJoin(Categ::tableName() . ' c',
                    'c.[[category_id]]=p2c.[[category_id]]')->andWhere('c.[[status]]=1');
        }

        return $query;
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
            'query' => $this->query,
            'sort' => [
                'route' => \Yii::$app->requestedRoute,
                'attributes' => [
                    'sort_order' => [
                        'asc' => ['m.[[sort_order]]' => SORT_ASC],
                        'desc' => ['m.[[sort_order]]' => SORT_DESC]
                    ]
                ],
                'defaultOrder' => [
                    'sort_order' => SORT_ASC
                ]
            ],
            'pagination' => [
                'route' => \Yii::$app->requestedRoute
            ]
        ], $config));
    }
}
