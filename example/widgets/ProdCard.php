<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

namespace app\widgets;

use app\models\Categ;
use app\models\Prod;
use dicr\oclib\Widget;
use Format;
use Html;
use Registry;
use yii\base\InvalidConfigException;

/**
 * Карточка товара.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class ProdCard extends Widget
{
    /** @var int */
    public const THUMB_WIDTH = 320;

    /** @var int */
    public const THUMB_HEIGHT = 240;

    /** @var \app\models\Prod */
    public $prod;

    /**
     * Консруктор.
     */
    public function init()
    {
        parent::init();

        if (! ($this->prod instanceof Prod)) {
            throw new InvalidConfigException('prod');
        }

        Html::addCssClass($this->options, 'widget-prod-card');

        if ($this->prod->categ->isCable) {
            Html::addCssClass($this->options, 'prod-cable');
            if ($this->prod->categ->inPath(Categ::ID_CABLEPROV)) {
                // помечаем категорию узких картинок для CSS
                Html::addCssClass($this->options, 'narrow-image');
            }
        }

        $this->prod->price = (float)$this->prod->price;

        $this->pluginOptions['prod'] = [
            'id' => $this->prod->product_id
        ];
    }

    /**
     * {@inheritDoc}
     * @throws \dicr\oclib\OcException
     */
    public function run()
    {
        echo Html::beginTag('div', $this->options);
        ?>
        <div class="p1">
            <img class="image" src="<?=$this->prodImage()?>" alt=""/>
        </div>

        <a class="name" href="<?=Html::esc($this->prod->url)?>"><?=Html::esc($this->prod->fullName)?></a>

        <div class="short_description"><?=Html::esc(Html::toText($this->prod->shortDescription))?></div>

        <div class="available">
            <div>Наличие:</div>
            <div>на удаленном складе <span class="kab-like"></span></div>
        </div>

        <div class="infobox">
            <div class="info card">
                <img class="img" src="/catalog/res/img/icon-card-red.png" alt=""/>
                <div class="popup">
                    <div class="inner">
                        <div class="title">Подробноси оплаты</div>
                        <div><a href="/oplata">Подробности оплаты</a></div>
                    </div>
                </div>
            </div>
            <div class="info delivery">
                <img class="img" src="/catalog/res/img/icon-delivery-red.png" alt=""/>
                <div class="popup">
                    <div class="inner">
                        <div class="title">Доставка по всей РФ</div>
                        <div><a href="/dostavka">Подробности доставки</a></div>
                    </div>
                </div>
            </div>
            <div class="info help">
                <img class="img" src="/catalog/res/img/icon-help-red.png" alt=""/>
                <div class="popup">
                    <div class="inner">
                        <div class="title">Зарудняетесь в выборе?</div>
                        <div><a href="/contacts">Свяжитесь с нами</a></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="price"><?=Format::toMoney($this->prod->price)?></div>

        <div class="addby">
            <button type="button" class="btn ctl minus">-</button>
            <input class="quantity" type="number" name="quantity" value="1" min="1" step="1"/>
            <span class="unit">м</span>
            <button type="button" class="btn ctl plus">+</button>
            <button type="button" class="btn tocart"><img src="/catalog/res/img/icon-cart-white.png" alt=""/></button>
        </div>

        <?php if ($this->prod->categ->isCable) { ?>
        <div class="optinfo">
            <div>от 100м - <?=Format::toMoney($this->prod->price * Prod::DISCOUNT_100, ['empty' => 'уточните'])?></div>
            <div>от 1000м - <?=Format::toMoney($this->prod->price * Prod::DISCOUNT_1000,
                    ['empty' => 'уточните'])?></div>
        </div>
    <?php } ?>

        <?php
        echo Html::endTag('div');
        echo $this->plugin('widgetProdCard');
    }

    /**
     * Подменяет отсутствующие картинки.
     *
     * @return string URL картинки
     * @throws \dicr\oclib\OcException
     */
    protected function prodImage()
    {
        $image = $this->prod->image;

        if (empty($image)) {
            if (! empty($this->prod->categ->image)) {
                $image = $this->prod->categ->image;
            } elseif ($this->prod->categ->topCategId === Categ::ID_IMPORTCABLE) {
                $image = 'catalog/import_cabel-zh.svg';
            } elseif ($this->prod->categ->topCategId === Categ::ID_CABLEPROV) {
                $image = 'kabs-gl.svg';
            } else {
                $image = $this->prod->categ->imageRecurse;
            }
        }

        return Registry::app()->load->model('tool/image')->resize($image, self::THUMB_WIDTH, self::THUMB_HEIGHT);
    }
}
