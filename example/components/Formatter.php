<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace app\components;

use Registry;

/**
 * Форматер.
 *
 * @package app\components
 */
class Formatter extends \yii\i18n\Formatter
{
    /**
     * Форматирует как деньги с учетом текущей валюты и курса.
     *
     * @param float $value
     * @return string
     */
    public function asMoney(float $value)
    {
        return Registry::app()->currency->format($value);
    }
}
