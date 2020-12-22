<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace app\widgets;

use app\models\City;
use dicr\oclib\Widget;
use Html;

/**
 * Виджет формы обратной связи
 *
 * @package app\widgets
 */
class FeedbackWidget extends Widget
{
    /** @var string путь файла прайс-листа */
    public $file = '/upload/price.xls';

    /**
     * Инициализация.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        Html::addCssClass($this->options, 'widget-feedback');

        $this->pluginOptions['metrika'] = City::current()->metrika;
    }

    /**
     * Рендерит ссылку скачивания прайса.
     */
    protected function renderPrice()
    {
        $filename = \DIR_HOME . $this->file;
        if (!is_file($filename)) {
            return;
        }
        ?>
        <div class="price">
            <label>Скачать прайс:</label><a href="<?=Html::esc($this->file)?>">.xls(<?=\Yii::$app->formatter->asShortSize(filesize($filename))?>)</a>
        </div>
        <?php
    }

    /**
     * Рендерит плагин.
     *
     * Функция должна выводить методом echo или возвращать string.
     */
    public function run()
    {
        echo Html::beginTag('div', $this->options);
        echo Html::cssLink('/catalog/res/widgets/feedback.css');
        ?>
        <div class="left">
            <div class="title">Индивидуальные условия заказа для оптовых клиетов</div>
            <?php $this->renderPrice() ?>
        </div>
        <div class="right">
            <button type="button" class="askprice btn">Узнать про скидки</button>
            <button type="button" class="order btn">Оставить заявку</button>
        </div>
        <?php
        echo Html::jsLink('/catalog/res/widgets/feedback.js');
        echo $this->plugin('widgetFeedback');
        echo Html::endTag('div');
    }
}
