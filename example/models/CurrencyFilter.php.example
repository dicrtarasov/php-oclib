<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 05.12.19 01:02:22
 */

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Фильтр валют.
 *
 * @property-read \yii\db\Query $query
 * @property-read \yii\data\ActiveDataProvider $provider
 *
 * @package app\models
 */
class CurrencyFilter extends Model
{
    public $code;

    /**
     * @inheritDoc
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'code' => 'Код'
        ];
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function rules()
    {
        return [
            ['code', 'trim'],
            ['code', 'default']
        ];
    }

    /**
     * Провадйер данных.
     *
     * @param array $config
     * @return \yii\data\ActiveDataProvider
     */
    public function getProvider(array $config = [])
    {
        return new ActiveDataProvider(array_merge([
            'query' => $this->getQuery()
        ], $config));
    }

    /**
     * Возвращает запрос.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuery()
    {
        $query = Currency::find();

        if (! $this->validate()) {
            return $query->where('0');
        }

        $query->andFilterWhere(['like', 'code', $this->code]);

        return $query;
    }

    /**
     * Названия валют, индексированный по id.
     *
     * @return array
     */
    public static function names()
    {
        static $names;

        if (!isset($names)) {
            $names = Currency::find()
                ->select(['title', 'currency_id'])
                ->orderBy('title')
                ->indexBy('currency_id')
                ->column();
        }

        return $names;
    }
}
