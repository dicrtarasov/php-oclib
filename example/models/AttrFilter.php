<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace app\models;

use Yii;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use function array_merge;
use function asort;
use const SORT_ASC;
use const SORT_DESC;
use const SORT_NATURAL;

/**
 * Фильтр характеристик.
 *
 * @package app\models
 * @property-read ActiveQuery $query
 * @property-read ActiveDataProvider $provider
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
     * @return ActiveDataProvider
     */
    public function getProvider(array $config = [])
    {
        return new ActiveDataProvider(array_merge([
            'query' => $this->getQuery(),
            'sort' => [
                'route' => Yii::$app->requestedRoute,
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
                'route' => Yii::$app->requestedRoute
            ]
        ], $config));
    }

    /**
     * Возвращает запрос характеристик.
     *
     * @return ActiveQuery
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

    /**
     * Возвращает все названия характеристик.
     *
     * @return string[] id => name
     */
    public static function listNames()
    {
        /** @var string[] $names */
        static $names;

        if (! isset($names)) {
            $names = Yii::$app->cache->getOrSet([__METHOD__], static function () {
                $names = [];

                /** @var Attr $attr */
                foreach (Attr::find()->all() as $attr) {
                    $names[(int)$attr->attribute_id] = $attr->name;
                }

                asort($names, SORT_NATURAL);
                return $names;
            }, null, new TagDependency([
                'tags' => [Attr::class]
            ]));
        }

        return $names;
    }
}
