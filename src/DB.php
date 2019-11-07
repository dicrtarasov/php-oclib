<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpMethodMayBeStaticInspection */
/** @noinspection PhpUnusedParameterInspection */

declare(strict_types = 1);
namespace dicr\oclib;

use PDO;
use stdClass;
use yii\db\Connection;
use yii\di\Instance;
use function count;
use function is_string;

/**
 * Прокси базы данных Opencart на Yii.
 *
 * @property string $lastId
 */
class DB
{
    /** @var \yii\db\Connection */
    public $db = 'db';

    /** @var int */
    private $affectedRows;

    /**
     * Constructor
     *
     * @param string $adaptor
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param string $database
     * @param int $port
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(
        $adaptor = null,
        $hostname = null,
        $username = null,
        $password = null,
        $database = null,
        $port = null)
    {
        $this->db = Instance::ensure($this->db, Connection::class);
    }

    /**
     * Запрос данных.
     *
     * @param string $sql
     * @return \stdClass|int|bool
     * @throws \yii\db\Exception
     */
    public function query(string $sql)
    {
        $cmd = $this->db->createCommand($sql);

        if (! preg_match('~^\s*(select|show)\s+~uim', $sql)) {
            return $this->affectedRows = $cmd->execute();
        }

        $ret = new stdClass();
        $ret->rows = $cmd->queryAll(PDO::FETCH_ASSOC);
        $ret->row = reset($ret->rows);
        $ret->num_rows = count($ret->rows);
        $this->affectedRows = 0;

        return $ret;
    }

    /**
     * Экранирование строки.
     *
     * @param string $value
     * @return string
     */
    public function escape($value)
    {
        return is_string($value) ? addcslashes(str_replace("'", "''", $value), "\000\n\r\\\032") : $value;
    }

    /**
     * Возвращает кол-во обновленных записей.
     *
     * @return int
     */
    public function countAffected()
    {
        return $this->affectedRows;
    }

    /**
     * Возвращает id последней записи.
     *
     * @return string
     */
    public function getLastId()
    {
        return $this->db->lastInsertID;
    }

    /**
     * Проверка подключения.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->db->isActive;
    }
}
