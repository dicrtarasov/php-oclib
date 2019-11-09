<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\widgets;

use dicr\oclib\Widget;
use Html;

/**
 * Список товаров.
 */
class ProdsList extends Widget
{
    /** @var \app\models\Prod[] $prods */
    public $prods;

    /** @var array опции виджеа карточки товара */
    public $itemConfig;

    /**
     * ProdsList constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        Html::addCssClass($this->options, 'widget-prods-list');

        $this->itemConfig = (array)($this->itemConfig ?: []);
    }

    /**
     * Рендеринг.
     *
     * @return string
     */
    public function run()
    {
        if (empty($this->prods)) {
            return;
        }

        echo Html::cssLink('/catalog/res/widgets/prods-list.css');
        echo Html::beginTag('div', $this->options);

        foreach ($this->prods as $prod) {
            echo ProdCard::widget(array_merge($this->itemConfig, [
                'prod' => $prod
            ]));
        }

        echo Html::endTag('div');
    }
}
