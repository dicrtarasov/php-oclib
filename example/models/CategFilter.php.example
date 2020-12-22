<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

namespace app\models;

use Filter;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\data\Sort;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\db\Query;
use function array_merge;
use function count;
use const SORT_ASC;
use const SORT_DESC;

/**
 * Фильр категорий.
 *
 * @property-read Query $query
 * @property-read Sort $sort
 * @property-read Pagination $pagination
 * @property-read ActiveDataProvider $provider
 *
 * @package app\models
 */
class CategFilter extends Model
{
    /** @var string сортировка по приоритету */
    public const SORT_ORDER = 'sort';

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
            [['category_id', 'parent_id'], function ($attribute) {
                $this->{$attribute} = Filter::ids($this->{$attribute}) ?: null;
            }],

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
     * @return ActiveQuery
     */
    public function getQuery()
    {
        $query = Categ::find()->alias('c')->select('c.*')->joinWith('desc', false, 'inner join');

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

    /** @var Sort */
    private $_sort;

    /**
     * Сортировка.
     *
     * @param array|null $config
     * @return Sort
     * @noinspection PhpUnused
     */
    public function getSort(array $config = null)
    {
        if (! isset($this->_sort)) {
            $defaultConfig = [
                'attributes' => [
                    self::SORT_ORDER => [
                        'asc' => [
                            'c.[[sort_order]]' => SORT_ASC
                        ],
                        'desc' => [
                            'c.[[sort_order]]' => SORT_DESC
                        ]
                    ]
                ],
                'defaultOrder' => [
                    self::SORT_ORDER => SORT_ASC
                ]
            ];

            $this->_sort = new Sort(array_merge($defaultConfig, $config ?: []));
        }

        return $this->_sort;
    }

    /** @var Pagination */
    private $_pagination;

    /**
     * Пагинация.
     *
     * @param array|null $config
     * @return Pagination
     */
    public function getPagination(array $config = null)
    {
        if (! isset($this->_pagination)) {
            $defaultConfig = [
                'pageSizeParam' => 'limit',
                'forcePageParam' => false
            ];

            $this->_pagination = new Pagination(array_merge($defaultConfig, $config ?: []));
        }

        return $this->_pagination;
    }

    /**
     * Возвращает провайдер данных.
     *
     * @param array|null $config
     * @return ActiveDataProvider
     * @noinspection PhpUnused
     */
    public function getProvider(array $config = null)
    {
        return new ActiveDataProvider(array_merge([
            'query' => $this->query,
            'sort' => $this->sort,
            'pagination' => $this->pagination
        ], $config ?: []));
    }

    /**
     * Возвращает полный список путей категорий.
     *
     * @return string[] id => pathname
     */
    public static function listPathnames()
    {
        /** @var string[] $pathnames */
        static $pathnames;

        if (! isset($pathnames)) {
            $pathnames = CategDesc::find()->alias('cd')
                ->innerJoin(Categ::tablePath() . ' cp', 'cp.[[path_id]]=cd.[[category_id]]')
                ->select([
                    'name' => new Expression('GROUP_CONCAT(cd.[[name]] ORDER BY cp.[[level]] SEPARATOR "/")'),
                    'id' => 'cp.[[category_id]]'
                ])
                ->where(['>', 'cp.[[path_id]]', 0])
                ->groupBy('cp.[[category_id]]')
                ->orderBy('name')
                ->indexBy('id')
                ->cache(true, new TagDependency([
                    'tags' => [Categ::class, CategDesc::class]
                ]))
                ->column();
        }

        return $pathnames;
    }
}
