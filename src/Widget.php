<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 27.04.21 12:13:20
 */

declare(strict_types = 1);
namespace dicr\oclib;

use dicr\helper\Html;

/**
 * Виджет.
 */
abstract class Widget extends \yii\bootstrap4\Widget
{
    /**
     * Генерирует HTML подключения плагина.
     *
     * @param string $name название плагина.
     */
    public function registerPlugin($name): void
    {
        echo Html::plugin('#' . $this->id, $name, $this->clientOptions);
    }
}
