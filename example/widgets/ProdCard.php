<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace app\widgets;

use app\models\Categ;
use app\models\Prod;
use dicr\oclib\Widget;
use Html;
use Registry;
use Yii;
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
     *
     * @throws \yii\base\InvalidConfigException
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
     * @throws \yii\base\Exception
     */
    public function run()
    {
        echo Html::beginTag('div', $this->options);
        ?>
        <div class="p1">
            <img class="image" src="<?=$this->prodImage()?>" alt=""/>
        </div>

        <a class="name" href="<?=Html::esc($this->prod->url)?>"><?=Html::esc($this->prod->fullName)?></a>

        <!-- noindex -->
        <div class="short_description"><?=Html::esc(Html::toText($this->prod->shortDescription))?></div><!-- /noindex -->

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
                        <div><a href="javascript:" data-toggle="modal" data-target="#modalCallback">Свяжитесь с нами</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="price"><?=! empty($this->prod->price) ? Yii::$app->formatter->asMoney($this->prod->price) :
                ''?></div>

        <div class="addby">
            <button type="button" class="btn ctl minus">-</button>
            <input class="quantity" type="number" name="quantity" value="1" min="1" step="1"/>
            <span class="unit"><?=Html::esc($this->prod->units)?></span>
            <button type="button" class="btn ctl plus">+</button>
            <button type="button" class="btn tocart"><img src="/catalog/res/img/icon-cart-white.png" alt=""/></button>
        </div>

        <?php if ($this->prod->categ->isCable && ! empty($this->prod->price)) { ?>
        <div class="optinfo">
            <div>от 100м - <?=Yii::$app->formatter->asMoney($this->prod->price * Prod::DISCOUNT_100)?></div>
            <div>от 1000м - <?=Yii::$app->formatter->asMoney($this->prod->price * Prod::DISCOUNT_1000)?></div>
        </div>
    <?php } ?>

        <?php
        echo $this->plugin('widgetProdCard');
        echo Html::endTag('div');
    }

    /**
     * Подменяет отсутствующие картинки.
     *
     * @return string URL картинки
     * @throws \yii\base\Exception
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
