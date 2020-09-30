<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 30.09.20 10:55:12
 */

declare(strict_types = 1);
namespace dicr\oclib;

use dicr\helper\Html;
use yii\base\BaseObject;

/**
 * Подключение статических ресурсов.
 */
class Asset extends BaseObject
{
    /** @var string[] */
    public static $links;

    /**
     * Выводит link rel=stylesheet
     *
     * @param string $href
     * @return string
     */
    public static function css(string $href) : string
    {
        if (isset(self::$links[$href])) {
            return '';
        }

        self::$links[$href] = true;

        return Html::cssLink($href);
    }

    /**
     * Возвращает script src.
     *
     * @param string $src
     * @return string
     */
    public static function js(string $src) : string
    {
        if (isset(self::$links[$src])) {
            return '';
        }

        self::$links[$src] = true;

        return Html::jsLink($src);
    }
}
