<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Ошибка базы данных.
 */
class DbException extends BaseException
{
    /**
     * Конструктор.
     *
     * @param string $error
     * @param string $sql
     */
    public function __construct(string $error, string $sql = null)
    {
        $msg = $error;
        if (! empty($sql)) {
            $msg .= "; \nSQL:" . $sql;
        }

        parent::__construct($msg);
    }
}
