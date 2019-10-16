<?php
namespace dicr\oclib;

/**
 * Модель базы данных.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Dao extends Model
{
    /**
     * возвращает базу данных.
     *
     * @return DB
     */
    public static function db()
    {
        return Registry::app()->get('db');
    }

    /**
     * Возвращает таблицу данных.
     *
     * @return string
     */
    public static function tableName()
    {
        return static::formName();
    }

    /**
     * Возвращает название поля id.
     *
     * @return string|null
     */
    public static function idName()
    {
        return null;
    }

    /**
     * Возвращает SQL-поля объекта.
     *
     * @throws \InvalidArgumentException
     * @return string[]
     */
    public function sqlFields()
    {
        $fields = [];

        foreach (static::rules() as $field => $rule) {
            // пропускаем пустые поля
            if (!isset($this->$field)) {
                continue;
            }

            // если в ачестве описания пользовательская функция
            if (is_callable($rule)) {
                $fields[$field] = sprintf('`%s`="%s"', $field, static::db()->esc($this->$field));
                continue;
            }

            // проверяем типы
            $type = $rule[0] ?? null;
            switch ($type) {
                case 'bool':
                    $fields[$field] = sprintf('`%s`=%d', $field, $this->$field ? 1 : 0);
                    break;

                case 'id':  // для ссылки null нужно 0
                    $fields[$field] = empty($this->$field) ? sprintf('`%s`=null', $field) : sprintf('`%s`=%d', $field, $this->$field);
                    break;

                case 'int':
                    $fields[$field] = sprintf('`%s`=%d', $field, $this->$field);
                    break;

                case 'float':
                    $fields[$field] = sprintf('`%s`=%s', $field, (float)$this->$field);
                    break;

                case 'string':
                case 'date':
                case 'set':
                    $fields[$field] = sprintf('`%s`="%s"', $field, static::db()->esc($this->$field));
                    break;

                default:
                    throw new \InvalidArgumentException('unknown type: ' . $type);
            }
        }

        return $fields;
    }

    /**
     * Сохраняем модель.
     *
     * @param bool $validate
     * @return int|bool
     */
    public function save(bool $validate = true)
    {
        if ($validate) {
            $this->validate();
        }

        $sqlFields = $this->sqlFields();
        if (empty($sqlFields)) {
            return false;
        }

        $idField = static::idName();
        if (!empty($idField) && !empty($this->$idField)) {
            static::db()->queryRes(sprintf(
                'update `%s` set %s where `%s`=%d',
                static::tableName(), implode(', ', $sqlFields), $idField, $this->$idField
            ));
        } else {
            static::db()->queryRes(sprintf(
                'insert into `%s` set %s',
                static::tableName(), implode(', ', $sqlFields)
            ));

            $this->$idField = static::db()->insertId();
        }

        return !empty($idField) ? $this->$idField : true;
    }

    /**
     * Возвращает запись с заданным id.
     *
     * @param int $id
     * @throws \LogicException
     * @return static|null
     */
    public static function get(int $id)
    {
        $idField = static::idName();
        if (empty($idField)) {
            throw new \LogicException('idName not implemented');
        }

        return static::db()->queryOne(sprintf(
            'select * from `%s` where `%s`=%d limit 1',
            static::tableName(), $idField, $id
        ), static::class);
    }

    /**
     * Возвращает список моделей.
     *
     * @param array $filter
     * @return int|static[]
     */
    public static function list(array $filter)
    {
        $sql = sprintf('select * from `%s`');

        if (!empty($filter['total'])) {
            return self::db()->queryScalar(sprintf(
                'select count(*) from (%s) T', $sql
            ));
        }

        if (!empty($filter['sort'])) {
            $sort = $filter['sort'];
            $order = SORT_ASC;
            if (substr($sort, 0, 1) === '-') {
                $sort = substr($sort, 1);
                $order = SORT_DESC;
            }

            $sql .= sprintf(' order by `%s` %s', $sort, $order == SORT_DESC ? 'desc' : '');
        }

        if (!empty($filter['offset']) || !empty($filter['limit'])) {
            $sql .= sprintf(' limit %d,%d', (int)($filter['offset'] ?? 0), (int)($filter['limit'] ?? 999999));
        }

        return static::db()->queryAll($sql, static::class);
    }
}