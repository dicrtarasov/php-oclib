<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license GPL-3.0-or-later
 * @version 25.06.21 10:21:48
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\db\Exception;
use yii\di\Instance;

use function is_string;
use function preg_match;

/**
 * Прокси базы данных Opencart на Yii.
 *
 * @property string $lastId
 */
class DB
{
    /** @var Connection */
    public $db = 'db';

    /** @var int */
    private $affectedRows;

    /**
     * Constructor
     *
     * @param ?string $adaptor
     * @param ?string $hostname
     * @param ?string $username
     * @param ?string $password
     * @param ?string $database
     * @param string|int|null $port
     * @throws InvalidConfigException
     * @noinspection PhpUnusedParameterInspection
     */
    public function __construct(
        string $adaptor = null,
        string $hostname = null,
        string $username = null,
        string $password = null,
        string $database = null,
        $port = null
    ) {
        $this->db = Instance::ensure($this->db, Connection::class);
    }

    /**
     * Экранирование строки.
     *
     * @param string|float|null $value
     * @return string|float|null
     */
    public function escape($value)
    {
        /** @var string|float|null $value */
        $value = Yii::$app->db->quoteValue($value);

        // удаляем кавычки по краям
        if (is_string($value) && mb_strpos($value, "'") === 0 && mb_substr($value, -1, 1) === "'") {
            $value = mb_substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Синоним escape.
     *
     * @param mixed $value
     * @return string|float|null
     */
    public function esc($value)
    {
        return $this->escape($value);
    }

    /**
     * Возвращает кол-во обновленных записей.
     *
     * @return int
     */
    public function countAffected() : int
    {
        return $this->affectedRows;
    }

    /**
     * Возвращает id последней записи.
     *
     * @return string
     */
    public function getLastId() : string
    {
        return $this->db->lastInsertID;
    }

    /**
     * Проверка подключения.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->db->isActive;
    }

    /**
     * Запрос данных.
     *
     * @param string $sql
     * @return DBResult|int
     * @throws Exception
     */
    public function query(string $sql)
    {
        $cmd = $this->db->createCommand($sql);

        if (! preg_match('~^\s*(select|show)\s+~ui', $sql)) {
            $this->affectedRows = (int)$cmd->execute();

            return $this->affectedRows;
        }

        $this->affectedRows = 0;

        return DBResult::fromCommand($cmd);
    }

    /**
     * Возвращает все строки результата.
     *
     * @param string $sql
     * @return array[]
     * @throws Exception
     */
    public function queryAll(string $sql): array
    {
        return $this->db->createCommand($sql)->queryAll() ?: [];
    }

    /**
     * Возвращает одну сроку результата.
     *
     * @param string $sql
     * @return ?array
     * @throws Exception
     */
    public function queryOne(string $sql) : ?array
    {
        return $this->db->createCommand($sql)->queryOne() ?: null;
    }

    /**
     * Возвращает колонку данных
     *
     * @param string $sql
     * @return array
     * @throws Exception
     */
    public function queryCol(string $sql) : array
    {
        return $this->db->createCommand($sql)->queryColumn() ?: [];
    }

    /**
     * Возвращает скалярное значение колонки первой строки
     *
     * @param string $sql
     * @return string|int|null|false
     * @throws Exception
     */
    public function queryScalar(string $sql)
    {
        $ret = $this->db->createCommand($sql)->queryScalar();

        return $ret === false ? null : $ret;
    }
}
