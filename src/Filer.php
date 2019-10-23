<?php
namespace dicr\oclib;

/**
 * Фильтр.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 *
 */
class Filter
{
    /**
     * Фильтрует список id.
     * @param int|array $val
     * @return int[]
     */
    public static function ids($val)
    {
        if (empty($val)) {
            return [];
        }

        $val = (array)$val;
        foreach ($val as $i => $id) {
            $val[$i] = (int)$id;
            if ($val[$i] < 1) {
                unset($val[$i]);
            }
        }

        if (!empty($val)) {
            $val = array_unique($val);
            sort($val, SORT_NUMERIC);
        }

        return $val;
    }

    /**
     * Фильрует масив строк.
     *
     * @param string|array $val
     * @return string[]
     */
    public static function strings($val)
    {
        if (empty($val)) {
            return [];
        }

        $val = (array)$val;
        foreach ($val as $i => $v) {
            $val[$i] = trim($v);
            if ($val[$i] === '') {
                unset($val[$i]);
            }
        }

        if (!empty($val)) {
            $val = array_unique($val);
            sort($val, SORT_STRING);
        }

        return $val;
    }
}
