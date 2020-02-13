<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 05.12.19 00:53:35
 */

namespace app\models;

use Country;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Фильтр стран.
 *
 * @package app\models
 *
 * @property-read \yii\db\ActiveQuery $query
 * @property-read \yii\data\ActiveDataProvider $provider
 */
class CountryFilter extends Model
{
    /** @var string */
    public $name;

    /** @var string домен */
    public $domain;

    /**
     * @inheritDoc
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Название',
            'domain' => 'Домен'
        ];
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function rules()
    {
        return [
            [['name', 'domain'], 'trim'],
            [['name', 'domain'], 'default']
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
        $query = Country::find();

        if (! $this->validate()) {
            return $query->where('0');
        }

        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['like', 'domain', $this->domain]);

        return $query;
    }
}
