<?php
namespace dicr\oclib;

/**
 * Html-helper.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Html
{
    /**
     * Экранирует строку HTML.
     *
     * @param string $val
     * @return string
     */
    public static function esc($val)
    {
        return htmlspecialchars($val, ENT_QUOTES, 'utf-8');
    }
}