<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace dicr\oclib;

use function strlen;

/**
 * Конвертор форматов.
 *
 * @package dicr\oclib
 */
class Format
{
    /**
     * Конвертирует в json
     *
     * @param mixed $obj
     * @return string json
     */
    public static function toJson($obj)
    {
        return json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Конвертирует в денежное значение.
     *
     * @param $val
     * @param array $options
     * @return string
     */
    public static function toMoney($val, $options = [])
    {
        $val = (float)$val;
        $empty = $options['empty'] ?? '';
        $decimals = $options['decimals'] ?? true;
        $valute = $options['valute'] ?? ' руб.';

        // рассчиываем количество десяичных
        if ($decimals === true) {
            $val = round($val, 2);
            $s = (string)$val;
            $pos = strpos($s, '.');
            $decimals = $pos !== false ? strlen($s) - $pos - 1 : 0;
        } else {
            $decimals = (int)$decimals;
            $val = round($val, $decimals);
        }

        return empty($val) ? $empty : number_format($val, $decimals, '.', ' ') . $valute;
    }
}
