<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\widgets;

use app\models\Attr;
use app\models\Categ;
use app\models\ProdFilter;
use dicr\oclib\Widget;
use Html;
use Registry;
use Yii;
use yii\base\InvalidConfigException;
use function count;
use function in_array;

/**
 * Виджет фильра товаров.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class CategFilterWidget extends Widget
{
    /** @var \app\models\Categ */
    public $categ;

    /** @var \app\models\ProdFilter модель фильтра */
    public $prodFilter;

    /**
     * Конструктор.
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

        Html::addCssClass($this->options, 'widget-categ-filter');
    }

    /**
     * {@inheritDoc}
     * @see \dicr\oclib\Widget::render()
     */
    public function run()
    {
        if (empty($this->prodFilter->categManufs) && empty($this->prodFilter->categAttrs)) {
            return;
        }

        echo Html::cssLink('/catalog/res/widgets/categ-filter.css');

        echo Html::beginTag('form', array_merge([
            'method' => 'GET',
            'action' => Registry::app()->url->link('product/category', [
                'category_id' => $this->categ->category_id
            ])
        ], $this->options));

        echo Html::hiddenInput('sort', Yii::$app->request->get('sort'));

        echo Html::tag('div', 'Фильтр', ['class' => 'title']);

        $this->renderManufs();
        $this->renderAttrs();

        echo Html::beginTag('div', ['class' => 'filter-buttons']);
        echo Html::button('Применить', ['type' => 'submit', 'class' => 'btn submit']);
        echo Html::button('Сбросить фильтр', ['type' => 'button', 'class' => 'btn reset']);
        echo Html::endTag('div');

        echo Html::endTag('form');

        echo Html::jsLink('/catalog/res/widgets/categ-filter.js');
        echo $this->plugin('widgetCategFilter');
    }

    /**
     * Рендерит производителей.
     */
    protected function renderManufs()
    {
        $manufs = $this->prodFilter->categManufs;
        if (count($manufs) < 2) {
            return;
        }

        echo Html::beginTag('div', ['class' => 'param manuf ' . (! empty($this->prodFilter->manufacturer_id) ? 'open' : '')]);
        echo Html::tag('div', 'Производитель', ['class' => 'name']);
        echo Html::beginTag('div', ['class' => 'values']);

        foreach ($manufs as $manuf) {
            echo Html::beginTag('div', ['class' => 'value']);

            echo Html::input('checkbox', 'm' . $manuf->manufacturer_id, 1, [
                'id' => $this->id . '-manuf-' . $manuf->manufacturer_id,
                'checked' => in_array((int)$manuf->manufacturer_id, (array)($this->prodFilter->manufacturer_id ?: []), false),
            ]);

            echo Html::tag('label', Html::esc($manuf->name), [
                'for' => $this->id . '-manuf-' . $manuf->manufacturer_id
            ]);

            echo Html::endTag('div');
        }

        echo Html::endTag('div'); // values
        echo Html::endTag('div'); // param
    }

    /**
     * Рендерит булеву характеристику.
     *
     * @param \app\models\Attr $attr
     */
    protected function renderFlagAttr(Attr $attr)
    {
        if (count($attr->values) < 2) {
            return;
        }

        $selected = in_array(1, (array)($this->prodFilter->attrs[$attr->attribute_id] ?? []), false);
        ?>
        <div class="param flag <?=$selected ? 'open' : ''?>">
            <div class="values">
                <div class="value">
                    <?=Html::input('checkbox', 'a' . $attr->attribute_id, 1, [
                        'id' => $this->id . '-attr-' . $attr->attribute_id,
                        'checked' => $selected
                    ])?>
                    <?=Html::tag('label', Html::esc($attr->name), [
                        'for' => $this->id . '-attr-' . $attr->attribute_id
                    ])?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Рендерит числовую характеристику.
     *
     * @param \app\models\Attr $attr
     */
    protected function renderNumberAttr(Attr $attr)
    {
        if (count($attr->values) < 2) {
            return;
        }

        $selected = (array)($this->prodFilter->attrs[$attr->attribute_id] ?? []);

        $vals = $attr->values;
        $min = reset($vals);
        $max = end($vals);
        ?>
        <div class="param number <?=$selected ? 'open' : ''?>">
            <div class="name"><?=Html::esc($attr->name)?></div>
            <div class="values">
                <div class="value">
                    <?=Html::input('number', 'a' . $attr->attribute_id . '[min]',
                        $selected['min'] ?? '', [
                            'min' => $min,
                            'max' => $max,
                            'placeholder' => 'от',
                            'step' => 0.001
                        ])?> -
                    <?=Html::input('number', 'a' . $attr->attribute_id . '[max]',
                        $selected['max'] ?? '', [
                            'min' => $min,
                            'max' => $max,
                            'placeholder' => 'до',
                            'step' => 0.001
                        ])?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Рендерит строковую характерисику.
     *
     * @param \app\models\Attr $attr
     */
    protected function renderStringAttr(Attr $attr)
    {
        if (count($attr->values) < 2) {
            return;
        }

        $selected = (array)($this->prodFilter->attrs[$attr->attribute_id] ?? []);
        ?>
        <div class="param string <?=$selected ? 'open' : ''?>">
            <div class="name"><?=Html::esc($attr->name)?></div>
            <div class="values">
                <?php foreach ($attr->values as $val) { ?>
                    <div class="value">
                        <?=Html::input('checkbox', 'a' . $attr->attribute_id . '[]', $val, [
                            'checked' => in_array($val, $selected, false),
                            'id' => $this->id . '-attr-' . $attr->attribute_id . '-' . $val,
                        ])?>
                        <?=Html::tag('label', $val, [
                            'for' => $this->id . '-attr-' . $attr->attribute_id . '-' . $val
                        ])?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    /**
     * Рендерит характеристики.
     */
    protected function renderAttrs()
    {
        foreach ($this->prodFilter->categAttrs as $attr) {
            if ($attr->type === Attr::TYPE_FLAG) {
                echo $this->renderFlagAttr($attr);
            } elseif ($attr->type === Attr::TYPE_NUMBER) {
                echo $this->renderNumberAttr($attr);
            } else {
                echo $this->renderStringAttr($attr);
            }
        }
    }
}
