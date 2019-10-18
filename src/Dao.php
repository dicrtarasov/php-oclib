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
    /** @var \dicr\oclib\DB */
    private static $_db;

    /**
     * возвращает базу данных.
     *
     * @return DB
     */
    public static function db()
    {
        if (!isset(self::$_db)) {
            self::$_db = Registry::app()->get('db');
        }

        return self::$_db;
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
     * Возвращает список ключевых полей.
     *
     * @return string[]
     */
    public static function keys()
    {
        return [];
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
     * Возвращает запись с заданным id.
     *
     * @param string|array $ids значение ключа id либо ассоциативный массив attr => val
     * @throws \LogicException
     * @return static|null
     */
    public static function get($conds)
    {
        if (empty($conds)) {
            throw new \InvalidArgumentException('empty conds');
        }

        if (!is_array($conds)) {
            $keys = static::keys();
            if (empty($keys)) {
                throw new \LogicException('no keys');
            }

            if (count($keys) != 1) {
                throw new \InvalidArgumentException('conds: в таблице более 1 поля в ключе');
            }

            $key = reset($keys);

            $conds = [
                $key => (string)$conds
            ];
        }

        $wheres = [];
        foreach ($conds as $attr => $val) {
            $wheres[$attr] = sprintf('`%s`="%s"', $attr, static::db()->esc($val));
        }

        return static::db()->queryOne(sprintf(
            'select * from `%s` where %s limit 1',
            static::tableName(), implode(' and ', $wheres)
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
        $sql = sprintf('select * from `%s`', static::tableName());

        if (!empty($filter['total'])) {
            return static::db()->queryScalar(sprintf(
                'select count(*) from (%s) T', $sql
            ));
        }

        if (!empty($filter['sort'])) {
            $sort = $filter['sort'];
            $order = SORT_ASC;
            if (substr($sort, 0, 1) === '-') {
                $sort = substr($sort, 1);
                $order = SORT_DESC;
            } elseif (($filter['order'] ?? '') == 'DESC') {
                $order = SORT_DESC;
            }

            $sql .= sprintf(' order by `%s` %s', $sort, $order == SORT_DESC ? 'desc' : '');
        }

        if (!empty($filter['start']) || !empty($filter['limit'])) {
            $sql .= sprintf(' limit %d,%d', (int)($filter['start'] ?? 0), (int)($filter['limit'] ?? 999999));
        }

        return static::db()->queryAll($sql, static::class);
    }

    /**
     * Создает новую модель.
     *
     * @param bool $validate
     * @return string|string[]
     */
    public function insert(bool $validate = true)
    {
        if ($validate) {
            $this->validate();
        }

        $sql = sprintf('insert into `%s`', static::tableName());

        $sqlFields = $this->sqlFields();
        if (!empty($sqlFields)) {
            $sql .= ' set ' . implode(', ', $sqlFields);
        }

        static::db()->queryRes($sql);

        $insertId = static::db()->insertId();

        // устанавливаем ключи
        // @TODO: непоняно в каком порядке и виде insertId возвращает ключи
        $keysVals = (array)$insertId;
        foreach (static::keys() as $attr) {
            $this->$attr = array_shift($keysVals);
        }

        return $insertId;
    }

    /**
     * Сохраняем модель.
     *
     * @param bool $validate
     * @return bool
     */
    public function update(bool $validate = true)
    {
        if ($validate) {
            $this->validate();
        }

        $sqlFields = $this->sqlFields();
        if (empty($sqlFields)) {
            return false;
        }

        // переносим ключевые поля из обновления в условие
        $keys = static::keys();
        $wheres = [];

        foreach ($keys as $attr) {
            if (!isset($sqlFields[$attr])) {
                throw new \LogicException('ключ ' . $attr . ' не усановлен');
            }

            $wheres[$attr] = $sqlFields[$attr];
            unset($sqlFields[$attr]);
        }

        static::db()->queryRes(sprintf(
            'update `%s` set %s where %s',
            static::tableName(), implode(', ', $sqlFields), implode(' and ', $wheres)
        ));

        return true;
    }

    /**
     * Вставляет/обновляет запись (on duplicate key update).
     *
     * @param bool $validate
     * @throws \LogicException
     * @throws DbException
     * @return string|string ключ
     * @see https://dev.mysql.com/doc/refman/8.0/en/insert-on-duplicate.html
     */
    public function upsert(bool $validate = true)
    {
        if ($validate) {
            $this->validate();
        }

        // все поля для вставки
        $insertFields = $this->sqlFields();
        if (empty($insertFields)) {
            throw new \LogicException('нет полей для вставки');
        }

        // готовим поля для обновения
        $updateFields = array_slice($insertFields, 0);

        // удаляем из полей обновления ключевые поля
        $keys = static::keys();
        foreach ($keys as $attr) {
            unset($updateFields[$attr]);
        }

        // если не полей для обновления, то on update эмулируем ключами
        if (empty($updateFields)) {
            $updateFields = [];
            foreach ($keys as $attr) {
                $updateFields[$attr] = sprintf('`%s`=`%s`', $attr, $attr);
            }
        }

        static::db()->queryRes(sprintf(
            'insert into `%s` set %s
            on duplicate key update %s',
            static::tableName(), implode(', ', $insertFields), implode(', ', $updateFields)
        ));

        $insertId = null;
        switch (static::db()->affectedRows()) {
            case 2: // запись была добавлена
                $insertId = static::db()->insertId();
                break;

            default: // 0, 1 - запись была обновлена или не тронута
                // формируем ключи из значений объекта
                $insertId = [];
                foreach ($keys as $attr) {
                    $insertId[] = $this->$attr;
                }

                if (count($insertId) == 1) {
                    $insertId = reset($insertId);
                }
        }

        return $insertId;
    }

    /**
     * Удалить объект.
     *
     * @throws \LogicException
     */
    public function delete()
    {
        $wheres = [];
        foreach (static::keys() as $attr) {
            $wheres[$attr] = sprintf('`%s`="%s"', $attr, static::db()->esc($this->$attr));
        }

        static::db()->queryRes(sprintf(
            'delete from `%s` where %s',
            static::tableName(), implode(' and ', $wheres)
        ));
    }
}