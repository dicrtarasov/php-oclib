<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use InvalidArgumentException;
use mysqli;
use mysqli_result;
use stdClass;

/**
 * База данных.
 */
class BaseDB extends AbstractObject
{
    /** @var \mysqli */
    private $link;

    /**
     * Конструктор.
     *
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param string $database
     * @throws \dicr\oclib\DbException
     */
    public function __construct($hostname, $username, $password, $database)
    {
        parent::__construct();

        $this->link = new mysqli($hostname, $username, $password, $database);

        if ($this->link->connect_error) {
            throw new DbException($this->link->connect_error);
        }

        $this->link->set_charset('utf8');
        $this->link->query("SET SQL_MODE = ''");
    }

    /*** Расширенные функии *****************************************************/

    /**
     * Возвращает все данные запроса.
     *
     * @param string $sql SQL
     * @param string $class класс объекта
     * @return array[]|\dicr\oclib\AbstractDao[]
     * @throws DbException
     */
    public function queryAll(string $sql, string $class = null)
    {
        $res = $this->queryRes($sql);
        $ret = [];

        while (true) {
            $row = ! empty($class) ? $res->fetch_object($class) : $res->fetch_assoc();
            if (empty($row)) {
                break;
            }

            $ret[] = $row;
        }

        $res->free();
        return $ret;
    }

    /**
     * Возвращает результат запроса.
     *
     * @param string $sql
     * @return \mysqli_result|boolean
     * @throws DbException
     */
    public function queryRes(string $sql)
    {
        $sql = trim($sql);
        if (empty($sql)) {
            throw new InvalidArgumentException('empty sql');
        }

        $ret = $this->link->query($sql);

        if ($this->link->errno) {
            throw new DbException($this->link->error, $sql);
        }

        return $ret;
    }

    /**
     * Возвращает колонку запроса.
     *
     * @param string $sql
     * @param string $column
     * @return string[]
     * @throws DbException
     */
    public function queryColumn(string $sql, string $column = null)
    {
        $ret = [];
        $res = $this->queryRes($sql);

        while ($row = $res->fetch_assoc()) {
            $ret[] = ! empty($column) ? $row[$column] : reset($row);
        }

        $res->free();
        return $ret;
    }

    /**
     * Возвращает одну запись.
     *
     * @param string $sql
     * @param string $class
     * @return array|\dicr\oclib\AbstractDao
     * @throws DbException
     */
    public function queryOne(string $sql, string $class = null)
    {
        $res = $this->queryRes($sql);
        $ret = ! empty($class) ? $res->fetch_object($class) : $res->fetch_assoc();
        $res->free();
        return $ret;
    }

    /**
     * Возвращает скалярный результат.
     *
     * @param string $sql
     * @param string $column
     * @return string
     * @throws DbException
     */
    public function queryScalar(string $sql, string $column = null)
    {
        $res = $this->queryRes($sql);
        $ret = $res->fetch_assoc();
        $res->free();

        if (empty($ret)) {
            return null;
        }

        if (! empty($column)) {
            return $ret[$column];
        }

        return reset($ret);
    }

    /**
     * Экранирование строки.
     *
     * @param string $value
     * @return string
     */
    public function escape($value)
    {
        return $this->esc($value);
    }

    /**
     * Экранирование строки.
     *
     * @param string $value
     * @return string
     */
    public function esc($value)
    {
        return $this->link->real_escape_string($value);
    }

    /**
     * Запрос данных.
     *
     * @param string $sql
     * @return \stdClass|int|bool
     * @throws DbException
     */
    public function query($sql)
    {
        $res = $this->queryRes($sql);

        if (! ($res instanceof mysqli_result)) {
            return $res;
        }

        $result = new stdClass();
        $result->rows = [];

        while ($row = $res->fetch_assoc()) {
            $result->rows[] = $row;
        }

        $result->num_rows = $res->num_rows;
        $result->row = $result->rows[0] ?? null;

        $res->free();
        return $result;
    }

    /*** Стандартные методы OpenCart ***********************************************/

    /**
     * Возвращает кол-во обновленных записей.
     *
     * @return int
     */
    public function countAffected()
    {
        return $this->affectedRows();
    }

    /**
     * Возвращает кол-во обновленных записей.
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->link->affected_rows;
    }

    /**
     * Возвращает id последней записи.
     *
     * @return int
     */
    public function getLastId()
    {
        return $this->insertId();
    }

    /**
     * Возвращает последний id.
     *
     * @return string
     */
    public function insertId()
    {
        return $this->link->insert_id;
    }

    /**
     * Деструктор.
     */
    public function __destruct()
    {
        if (! empty($this->link)) {
            $this->link->close();
        }
    }
}
