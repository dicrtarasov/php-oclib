<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use function is_array;

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
     *
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
            /** @noinspection OffsetOperationsInspection */
            $val[$i] = (int)$id;
            /** @noinspection OffsetOperationsInspection */
            if ($val[$i] < 1) {
                /** @noinspection OffsetOperationsInspection */
                unset($val[$i]);
            }
        }

        if (! empty($val)) {
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
            $val[$i] = (string)$v;
            if ($val[$i] === '') {
                unset($val[$i]);
            }
        }

        if (! empty($val)) {
            $val = array_unique($val);
            sort($val, SORT_STRING);
        }

        return $val;
    }

    /**
     * Фильтрует аргументы запроса рекурсивно, удаляя пустые параметры
     *
     * @param array $args
     * @return array
     */
    public static function params(array $args)
    {
        foreach ($args as $i => $v) {
            if (is_array($v)) {
                $args[$i] = static::params($v);
                if (empty($args[$i])) {
                    unset($args[$i]);
                }
            } elseif ($v === null || $v === '') {
                unset($args[$i]);
            }
        }

        return $args;
    }
}
