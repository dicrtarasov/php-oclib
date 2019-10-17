<?php
namespace dicr\oclib;

/**
 * База данных.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class DB
{
    /** @var \mysqli */
    private $link = null;

    /**
     * Конструктор.
     *
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param string $database
     * @param int $port
     * @throws DbException
     */
	public function __construct($hostname, $username, $password, $database, $port = '3306')
	{
	    $this->link = new \mysqli($hostname, $username, $password, $database);

		if ($this->link->connect_error) {
			throw new DbException($this->connect_error);
		}

		$this->link->set_charset("utf8");
		$this->link->query("SET SQL_MODE = ''");
	}

    /*** Расширенные функии *****************************************************/

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
	 * Возвращает последний id.
	 *
	 * @return string
	 */
	public function insertId()
	{
	    return $this->link->insert_id;
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
	 * Возвращает результат запроса.
	 *
	 * @param string $sql
	 * @throws DbException
	 * @return \mysqli_result|boolean
	 */
	public function queryRes(string $sql)
	{
		$sql = trim($sql);
		if (empty($sql)) {
		    throw new \InvalidArgumentException('empty sql');
		}

		$ret = $this->link->query($sql);

		if ($this->link->errno) {
		    throw new DbException($this->link->error, $sql);
		}

		return $ret;
	}

	/**
	 * Возвращает все данные запроса.
	 *
	 * @param string $sql SQL
	 * @param string $class класс объекта
	 * @throws DbException
	 * @return array[]|$class[]
	 */
	public function queryAll(string $sql, string $class = null)
	{
	    $res = $this->queryRes($sql);
	    $ret = [];

	    while (true) {
	        $row = !empty($class) ? $res->fetch_object($class) : $res->fetch_assoc();
	        if (empty($row)) {
	            break;
	        }

	        $ret[] = $row;
	    }

	    $res->free();
	    return $ret;
	}

	/**
	 * Возвращает колонку запроса.
	 *
	 * @param string $sql
	 * @param string $column
	 * @throws DbException
	 * @return string[]
	 */
	public function queryColumn(string $sql, string $column = null)
	{
	    $ret = [];
	    $res = $this->queryRes($sql);

	    while ($row = $res->fetch_assoc()) {
	        $ret = !empty($column) ? $row[$column] : reset($row);
	    }

	    $res->free();
	    return $ret;
	}

	/**
	 * Возвращает одну запись.
	 *
	 * @param string $sql
	 * @param string $class
	 * @throws DbException
	 * @return array|$class
	 */
	public function queryOne(string $sql, string $class = null)
	{
	    $res = $this->queryRes($sql);
	    $ret = !empty($class) ? $res->fetch_object($class) : $res->fetch_assoc();
	    $res->free();
	    return $ret;
	}

	/**
	 * Возвращает скалярный результат.
	 *
	 * @param string $sql
	 * @param string $column
	 * @throws DbException
	 * @return string
	 */
	public function queryScalar(string $sql, string $column = null)
	{
	    $res = $this->queryRes($sql);
	    $ret = $res->fetch_assoc();
	    $res->free();
	    return !empty($column) ? $ret[$column] : reset($ret);
	}

    /*** Стандартные методы OpenCart ***********************************************/

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
	 * Запрос данных.
	 *
	 * @param string $sql
	 * @throws DbException
	 * @return \stdClass|int|bool
	 */
	public function query($sql)
	{
		$res = $this->queryRes($sql);

		if (!($res instanceof \mysqli_result)) {
		    return $res;
		}

		$result = new \stdClass();
		$result->rows = [];

		while ($row = $res->fetch_assoc()) {
		    $result->rows[] = $row;
		}

		$result->num_rows = $res->num_rows;
		$result->row = $result->rows[0] ?? null;

		$res->free();
		return $result;
	}

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
	 * Возвращает id последней записи.
	 *
	 * @return int
	 */
	public function getLastId()
	{
		return $this->insertId();
	}

	/**
	 * Деструктор.
	 */
	public function __destruct()
	{
	    if (!empty($this->link)) {
	        $this->link->close();
	    }
	}
}