<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 24.08.20 17:03:48
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\db\Exception;
use yii\di\Instance;

use function is_string;

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
     * @param ?string $port
     * @throws InvalidConfigException
     * @noinspection PhpUnusedParameterInspection
     */
    public function __construct(
        $adaptor = null,
        $hostname = null,
        $username = null,
        $password = null,
        $database = null,
        $port = null
    ) {
        $this->db = Instance::ensure($this->db, Connection::class);
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

        if (! preg_match('~^\s*(select|show)\s+~uim', $sql)) {
            $this->affectedRows = (int)$cmd->execute();

            return $this->affectedRows;
        }

        $this->affectedRows = 0;

        return DBResult::fromCommand($cmd);
    }

    /**
     * Экранирование строки.
     *
     * @param string|float|null $value
     * @return string|float|null
     */
    public function escape($value)
    {
        $value = Yii::$app->db->quoteValue($value);

        if (is_string($value)) {
            $value = (string)$value;

            // удаляем кавычки по краям
            if (mb_strpos($value, "'") === 0 && mb_substr($value, -1, 1) === "'") {
                $value = mb_substr($value, 1, -1);
            }
        }

        return $value;
    }

    /**
     * Синоним escape.
     *
     * @param mixed $value
     * @return mixed
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
    public function countAffected(): int
    {
        return $this->affectedRows;
    }

    /**
     * Возвращает id последней записи.
     *
     * @return string
     */
    public function getLastId(): string
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
}
