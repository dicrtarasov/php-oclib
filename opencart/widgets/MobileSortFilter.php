<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\widgets;

use app\models\Categ;
use app\models\ProdFilter;
use dicr\oclib\Widget;
use Html;
use Registry;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/**
 * Виджет панели сортировки/фильтра для мобильного.
 */
class MobileSortFilter extends Widget
{
    /** @var \app\models\Categ */
    public $categ;

    /** @var \app\models\ProdFilter */
    public $prodFilter;

    /** @var bool отображать сортировку */
    public $enableSort = true;

    /** @var bool отображать фильтр */
    public $enableFilter = true;

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

        if (! ($this->prodFilter instanceof ProdFilter)) {
            throw new InvalidConfigException('prodFilter');
        }

        Html::addCssClass($this->options, 'widget-mobile-sort-filter');
    }

    /**
     * Рендеринг.
     */
    public function run()
    {
        echo Html::beginTag('div', $this->options);
        echo Html::cssLink('/catalog/res/widgets/mobile-sort-filter.css');

        $this->renderButtons();

        if ($this->enableSort) {
            $this->renderSort();
        }

        if ($this->enableFilter) {
            $this->renderFilter();
        }

        echo Html::jsLink('/catalog/res/widgets/mobile-sort-filter.js');
        echo $this->plugin('widgetMobileSortFilter');
        echo Html::endTag('div'); // widget
    }

    /**
     * Рендерит панель кнопок.
     */
    protected function renderButtons()
    {
        echo Html::beginTag('div', ['class' => 'sortfilter-buttons']);

        if ($this->enableSort) {
            echo Html::button('Сортировка', ['class' => 'btn btn-sort', 'data-target' => 'sort']);
        }

        if ($this->enableFilter) {
            echo Html::button('Фильтр', ['class' => 'btn btn-filter', 'data-target' => 'filter']);
        }

        echo Html::endTag('div');
    }

    /**
     * Рендери элемент сортировки.
     */
    protected function renderSort()
    {
        $sort = Yii::$app->request->get('sort');
        ?>
        <div class="popup popup-sort">
            <div class="sort-links">
                <?=Html::a('Дороже', Registry::app()->url->link('product/category',
                    array_merge(Registry::app()->request->get, ['sort' => '-price'])),
                    $sort === '-price' ? ['class' => 'current'] : [])?>

                <?=Html::a('Дешевле', Registry::app()->url->link('product/category',
                    array_merge(Registry::app()->request->get, ['sort' => 'price'])),
                    $sort === 'price' ? ['class' => 'current'] : [])?>
            </div>
        </div>
        <?php
    }

    /**
     * Рендерит фильтр.
     */
    protected function renderFilter()
    {
        ?>
        <?=CategFilterWidget::widget([
            'categ' => $this->categ,
            'prodFilter' => $this->prodFilter,
            'options' => [
                'class' => 'popup popup-filter'
            ]
        ])?>
        <?php
    }
}
