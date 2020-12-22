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
use const SORT_ASC;
use const SORT_DESC;

/**
 * Фильр ЧПУ алиасов.
 *
 * @property-read \yii\db\ActiveQuery $filterQuery
 * @property-read \yii\data\ActiveDataProvider $provider
 *
 * @package dicr\oclib
 */
class UrlAliasFilter extends Model
{
    /** @var string */
    public $query;

    /** @var string keyword */
    public $keyword;

    /** @var string */
    public $type;

    /**
     * Правила валидации.
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['query', 'keyword'], 'trim'],
            [['query', 'keyword'], 'default'],

            ['type', 'default'],
            ['type', 'in', 'range' => array_keys(UrlAlias::TYPES)]
        ];
    }

    /**
     * Возвращает запрос алиасов.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFilterQuery()
    {
        $query = UrlAlias::find()->alias('ua')->select('ua.*')->addSelect([
            'type' => sprintf('if([[query]] rlike "^([a-z0-9_-]+/)+[a-z0-9_-]+$", "%s", if([[query]] rlike "^[[:alnum:]]+_id=[[:digit:]]+$", "%s", "%s"))',
                UrlAlias::TYPE_ROUTE, UrlAlias::TYPE_OBJECT, UrlAlias::TYPE_PARAMS)
        ]);

        if (! $this->validate()) {
            return $query->where('1=0');
        }

        $query->andFilterWhere(['like', 'query', $this->query]);
        $query->andFilterWhere(['like', 'keyword', $this->keyword]);
        $query->andFilterHaving(['type' => $this->type]);

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
            'query' => $this->filterQuery,
            'sort' => [
                'route' => \Yii::$app->requestedRoute,
                'attributes' => [
                    'query' => [
                        'asc' => ['query' => SORT_ASC],
                        'desc' => ['query' => SORT_DESC],
                        'label' => 'Маршрут/параметры'
                    ],
                    'keyword' => [
                        'asc' => ['keyword' => SORT_ASC],
                        'desc' => ['keyword' => SORT_DESC],
                        'label' => 'Алиас ЧПУ'
                    ],
                    'type' => [
                        'asc' => ['type' => SORT_ASC],
                        'desc' => ['type' => SORT_DESC],
                        'label' => 'Тип алиаса'
                    ]
                ],
                'defaultOrder' => [
                    'keyword' => SORT_ASC
                ]
            ],
            'pagination' => [
                'route' => \Yii::$app->requestedRoute
            ]
        ], $config));
    }
}
