<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\helpers\Json;

/**
 * Html-helper.
 */
class Html extends \dicr\helper\Html
{
    /**
     * HTML select
     *
     * @param string $name имя select
     * @param string|null $value текущее значение
     * @param array $vals значения val => text
     * @param array $options аттрибуты
     * @return string
     */
    public static function select(string $name, string $value, array $vals, array $options = [])
    {
        return static::dropDownList($name, $value, $vals, $options);
    }

    /**
     * Начало тега
     *
     * @param string $name
     * @param array $attrs
     * @return string
     */
    public static function startTag(string $name, array $attrs = [])
    {
        return static::beginTag($name, $attrs);
    }

    /**
     * Конвертирует в json
     *
     * @param mixed $obj
     * @return string json
     */
    public static function json($obj)
    {
        return Json::encode($obj);
    }
}
