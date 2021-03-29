<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 29.03.21 13:20:58
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
     * @param array $options опции Html::cssFile
     * @return string
     */
    public static function css(string $href, array $options = []): string
    {
        if (isset(self::$links[$href])) {
            return '';
        }

        self::$links[$href] = true;

        return Html::cssFile($href, $options);
    }

    /**
     * Возвращает script src.
     *
     * @param string $src
     * @param array $options опции Html::jsFile
     * @return string
     */
    public static function js(string $src, array $options = []): string
    {
        if (isset(self::$links[$src])) {
            return '';
        }

        self::$links[$src] = true;

        return Html::jsFile($src, $options);
    }
}
