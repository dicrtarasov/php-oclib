<?php
namespace dicr\oclib;

/**
 * Ошибка базы данных.
 */
class DbException extends Exception
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
        if (!empty($sql)) {
            $msg .= "; \nSQL:" . $sql;
        }

        parent::__construct($msg);
    }
}
