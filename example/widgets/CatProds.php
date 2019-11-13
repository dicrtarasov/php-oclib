<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\widgets;

use app\models\Categ;
use app\models\CategFilter;
use Debug;
use dicr\oclib\Widget;
use Html;
use Registry;
use yii\base\InvalidConfigException;
use yii\data\Pagination;

/**
 * Виджет списка категорий с примерами витринных товаров в каждой.
 *
 * @package app\widgets
 */
class CatProds extends Widget
{
    /** @var \app\models\Categ */
    public $categ;

    /** @var bool */
    public $recurse = false;

    /** @var int */
    public $pageSize = 5;

    /** @var \yii\data\ActiveDataProvider */
    protected $_categsProvider;

    /**
     * Инициализация.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (! ($this->categ instanceof Categ)) {
            throw new InvalidConfigException('categ');
        }

        Html::addCssClass($this->options, 'widget-cat-prods');
    }

    /**
     * Рендер.
     */
    public function run()
    {
        $provider = $this->getCategsProvider();
        if ($provider->count < 1) {
            return;
        }

        $this->addPaginationLinks();

        echo Html::cssLink('/catalog/res/widgets/cat-prods.css');

        echo Html::beginTag('div', $this->options);
        foreach ($provider->models as $subcateg) {
            $this->renderCateg($subcateg);
        }

        /** @var \Pagination $pagination */
        $pagination = new \Pagination($provider);
        echo $pagination->render();

        echo Html::endTag('div');
    }

    /**
     * Провайдер категорий.
     *
     * @return \yii\data\ActiveDataProvider
     */
    protected function getCategsProvider()
    {
        if (! isset($this->_categsProvider)) {
            // фильтр категорий
            $filter = new CategFilter([
                'parent_id' => $this->categ->category_id,
                'status' => 1,
                'recurse' => $this->recurse
            ]);

            $this->_categsProvider = $filter->getProvider([
                'sort' => [
                    'route' => \Yii::$app->requestedRoute
                ],
                'pagination' => [
                    'route' => \Yii::$app->requestedRoute,
                    'defaultPageSize' => $this->pageSize,
                    'validatePage' => false
                ]
            ]);
        }

        return $this->_categsProvider;
    }

    /**
     * Добавляет ссылки пагинации в документ.
     */
    protected function addPaginationLinks()
    {
        $provider = $this->getCategsProvider();
        $links = $provider->pagination->getLinks();

        if (! empty($links[Pagination::LINK_NEXT])) {
            Registry::app()->document->addLink($links[Pagination::LINK_NEXT], 'next');
        }
    }

    /**
     * Рендерит категорию.
     *
     * @param \app\models\Categ $categ
     */
    protected function renderCateg(Categ $categ)
    {
        echo Html::beginTag('div', ['class' => 'categ']);

        $totalProdsCount = $categ->getProdsCount([
            'recurse' => true,
            'status' => 1
        ]);

        echo Html::beginTag('div', ['class' => 'categ-title']);
        echo Html::a(Html::esc($categ->name), $categ->url, ['class' => 'name']);
        echo Html::tag('sup', $totalProdsCount > 0 ? number_format($totalProdsCount, 0) : '', ['class' => 'quantity']);
        echo Html::endTag('div');

        $microrazm = trim(Html::toText($categ->microrazm));
        if (! empty($microrazm)) {
            echo Html::tag('div', Html::esc($microrazm), ['class' => 'desc']);
        }

        echo ProdsList::widget([
            'prods' => $categ->frontProds
        ]);

        echo Html::endTag('div');
    }
}
