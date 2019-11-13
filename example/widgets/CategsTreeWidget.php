<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

namespace app\widgets;

use app\models\Categ;
use dicr\oclib\Url;
use dicr\oclib\Widget;
use Html;
use Registry;
use yii\base\InvalidConfigException;

/**
 * Виджет категорий для боковой панели.
 */
class CategsTreeWidget extends Widget
{
    /** @var string[] группы категорий для кабелей (по свойству camenimg) */
    public const GROUPS_CABLE = [
        1 => 'Кабели связи',
        2 => 'Для энергетики',
        3 => 'Для транспортной инфраструктуры',
        4 => 'Для строительства',
        5 => 'Для машиностроения',
        6 => 'Для нефтегазовой промышленности',
        7 => 'Для горнодобывающей промышленности'
    ];

    /** @var \app\models\Categ родительская категория */
    public $categ;

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

        Html::addCssClass($this->options, 'widget-categs-tree');
    }

    /** @var \app\models\Categ[] */
    private $_childs;

    /**
     * Возвращает дочерние категории.
     *
     * @return \app\models\Categ[]
     */
    public function getChilds()
    {
        if (! isset($this->_childs)) {
            $this->_childs = $this->categ->getChilds()
                ->andWhere(['[[status]]' => 1])
                ->joinWith('desc', true)
                ->orderBy('{{oc_category_description}}.[[name]]')
                ->all();
        }

        return $this->_childs;
    }

    /**
     * {@inheritDoc}
     */
    public function run()
    {
        $categs = $this->getChilds();
        if (empty($categs)) {
            return;
        }

        echo '<!-- noindex -->';
        echo Html::cssLink('/catalog/res/widgets/categs-tree.css');
        echo Html::beginTag('div', $this->options);

        if ((int)$this->categ->category_id === Categ::ID_CABLEPROV) {
            $this->renderCableList($categs);
        } else {
            printf('<div class="name">%s</div>', Html::esc($this->categ->name));
            $this->renderList($categs);
        }
        echo Html::endTag('div');
        echo '<!-- /noindex -->';
    }

    /**
     * Выводит список заданных категорий.
     *
     * @param Categ[] $categs
     */
    protected function renderList(array $categs)
    {
        if (empty($categs)) {
            return;
        }

        echo '<ul>';

        foreach ($categs as $categ) {
            echo Html::tag('li',
                Html::a(Html::esc($categ->name), $categ->url, ['class' => 'blue-link'])
            );
        }

        echo '</ul>';
    }

    /**
     * Рендерит кабельные категории по разделам.
     *
     * @param \app\models\Categ[] $categs
     */
    protected function renderCableList(array $categs)
    {
        if (empty($categs)) {
            return;
        }

        // разбиваем категории по разделам
        $divs = [];
        foreach ($categs as $categ) {
            $divs[$categ->catmenimg][] = $categ;
        }

        ksort($divs);

        echo '<ul>';

        /** @noinspection SuspiciousLoopInspection */
        foreach ($divs as $group_id => $categs) {
            $group_name = self::GROUPS_CABLE[$group_id] ?? null;
            if (empty($group_name)) {
                continue;
            }

            echo '<li>';
            echo Html::tag('div', Html::esc($group_name), ['class' => 'name']);
            $this->renderList($categs);
            echo '</li>';
        }

        echo '</ul>';
    }
}
