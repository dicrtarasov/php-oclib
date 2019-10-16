<?php
namespace dicr\oclib;

/**
 * Класс для отладки.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Debug
{
    /**
     * Дамп значения в html.
     *
     * @param mixed $val
     * @param bool $exit выйти после дампа
     */
    public static function xmp($val, bool $exit = true)
    {
        if (!DEBUG) {
            return;
        }

        echo '<xmp>';
        var_dump($val);

        if ($exit) {
            exit;
        }
    }
}