<?php
namespace dicr\oclib;

/**
 * Базовая модель.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Model extends ArrayObject
{
    /**
     * Имя формы.
     *
     * @return string
     */
    public static function formName()
    {
        $path = explode('\\', static::class);
        return array_pop($path);
    }

    /**
     * Возвращает список полей модели.
     *
     * @return array [ name => text ]
     */
    public static function attributes()
    {
        $ref = new \ReflectionObject(static::class);
        $attrs = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
        return array_combine($attrs, $attrs);
    }

    /**
     * Правила валидации полей.
     *
     * Ассоциативный массив, где ключ - имя поля, значение - описание типа
     * или callable(BaseModel $model, string $field) : ValidateException
     *
     * Описание типа:
     *     ассоциативный массив [0 => <тип>, req => x, def => по умолчанию ...]
     *     req -> обязательное поле при создании нового обьекта (не пустой id), 0/1
     *     def -> значение по-умолчанию
     *
     * Типы:
     *    bool - флаг да/нет
     *    id - ссылка на id, null => да/не может быть 0
     *    int - целое число, min, max
     *    float - натуральное число, min, max
     *    string - строка, min => минимальная длина, max - максимальная длина
     *    date - дата/время, format - формат даты,
     *    set - множество значений, vals => значения
     *
     * return string[] ассоциативный массив
     */
    public static function rules()
    {
        return [];
    }

    /**
     * Валидация модели.
     *
     * @throws ValidateException
     */
    public function validate()
    {
        foreach (static::rules() as $field => $rule) {
            // если в ачестве описания пользовательская функция
            if (is_callable($rule)) {
                call_user_func($rule, $this, $field);
                continue;
            }

            // проверяем правило
            if (!is_array($rule) || !is_string($rule[0] ?? null)) {
                throw new \InvalidArgumentException('Ошибка правил валидации модели: ' . static::class);
            }

            // установка значения по-умолчанию
            if (!isset($this->$field) && isset($rule['def'])) {
                $this->$field = $rule['def'];
            }

            // проверка наличие значения
            if (!isset($this->$field)) {
                if (!empty($rule['req'])) {
                    throw new ValidateException($this, $field, 'требуется значение');
                } else {
                    // пропускаем валидацию незаполненого значения
                    continue;
                }
            }

            // проверяем типы
            $type = $rule[0] ?? null;
            switch ($type) {
                case 'bool':
                    // конвертируем в булево
                    $this->$field = boolval($this->$field);
                    break;

                case 'id':  // для ссылки null нужно 0
                    // конверируем в id
                    if (!is_numeric($this->$field)) {
                        throw new ValidateException($this, $field);
                    }

                    $this->$field = intval($this->$field);

                    if ($this->$field < 0) {
                        throw new ValidateException($this, $field);
                    } elseif (empty($this->$field)) {
                        if (empty($rule['null'])) {
                            throw new ValidateException($this, $field);
                        }
                    }

                    break;

                case 'int':
                    if (!is_numeric($this->$field)) {
                        throw new ValidateException($this, $field);
                    }

                    $this->$field = intval($this->$field);

                    if (
                        (isset($rule['min']) && $this->$field < $rule['min']) ||
                        (isset($rule['max']) && $this->$field > $rule['max'])
                    ) {
                        throw new ValidateException($this, $field);
                    }

                    break;

                case 'float':
                    if (!is_numeric($this->$field)) {
                        throw new ValidateException($this, $field);
                    }

                    $this->{$field} = floatval($this->{$field});

                    if (
                        (isset($rule['min']) && $this->$field < $rule['min']) ||
                        (isset($rule['max']) && $this->$field > $rule['max'])
                    ) {
                        throw new ValidateException($this, $field);
                    }

                    break;

                case 'string':
                    $this->$field = trim($this->{$field});
                    $len = mb_strlen($this->{$field});

                    if (
                        (isset($rule['min']) && $len < $rule['min']) ||
                        (isset($rule['max']) && $len > $rule['max'])
                    ) {
                        throw new ValidateException($this, $field);
                    }

                    break;

                case 'date':
                    $val = is_numeric($this->$field) ? (int)$this->$field : strtotime($this->{$field});
                    if (empty($val) || $val < 0) {
                        throw new ValidateException($this, $field);
                    }

                    $this->$field = date($rule['format'] ?? 'Y-m-d H:i:s', $val);

                    break;

                case 'set':
                    $vals = (array)($rule['vals'] ?? []);
                    if (empty($vals)) {
                        throw new ValidateException($this, $field);
                    }

                    if (!in_array($this->$field, $vals)) {
                        throw new ValidateException($this, $field);
                    }

                    break;

                default:
                    throw new \InvalidArgumentException('invalid type: ' . $type);
            }
        }
    }

    /**
     * Загрузка данных (только аттрибуты из attributes())
     *
     * @param array $data
     * @param string $formName
     * @param bool $skipEmpty пропускать пустые поля
     */
    public function load(array $data, string $formName = null, bool $skipEmpty = false)
    {
        if (!isset($formName)) {
            $formName = static::formName();
        }

        // подстраиваем форму
        if ($formName !== '') {
            $data = $data[$formName] ?? [];
        }

        // выбираем значения для конфига
        $config = [];
        foreach (array_keys(static::attributes()) as $attr) {
            if (array_key_exists($data[$attr])) {
                if (($data[$attr] === null || $data[$attr] === '') && $skipEmpty) {
                    continue;
                }

                $config[$attr] = $data[$attr];
            }
        }

        if (!empty($config)) {
            $this->configure($config);
        }
    }

    /**
     * Конвертирование в массив (только из списка аттрибутов).
     *
     * @return array
     */
    public function toArray()
    {
        $arr = [];

        foreach ($this->attributes() as $attr) {
            if (isset($this->$attr)) {
                $arr[$attr] = $this->$attr;
            }
        }

        return $arr;
    }
}