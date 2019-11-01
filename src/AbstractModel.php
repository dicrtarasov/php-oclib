<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;

/**
 * Абстрактная базовая модель.
 */
abstract class AbstractModel extends AbstractObject
{
    /** @var string */
    private static $_formName;

    /** @var string[] */
    private static $_attributes;

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
                $rule($this, $field);
                continue;
            }

            // проверяем правило
            if (! is_array($rule) || ! is_string($rule[0] ?? null)) {
                throw new InvalidArgumentException('Ошибка правил валидации модели: ' . static::class);
            }

            // установка значения по-умолчанию
            if (! isset($this->{$field}) && isset($rule['def'])) {
                $this->{$field} = $rule['def'];
            }

            // проверка наличие значения
            if (! isset($this->{$field})) {
                if (! empty($rule['req'])) {
                    throw new ValidateException($this, $field, 'требуется значение');
                }

                // пропускаем валидацию незаполненого значения
                continue;
            }

            // проверяем типы
            $type = $rule[0] ?? null;
            switch ($type) {
                case 'bool':
                    // конвертируем в булево
                    $this->{$field} = (bool)$this->{$field};
                    break;

                case 'id':  // для ссылки null нужно 0
                    // конверируем в id
                    if (! is_numeric($this->{$field})) {
                        throw new ValidateException($this, $field);
                    }

                    $this->{$field} = (int)$this->{$field};

                    if ($this->{$field} < 0) {
                        throw new ValidateException($this, $field);
                    }

                    if (empty($this->{$field}) && empty($rule['null'])) {
                        throw new ValidateException($this, $field);
                    }

                    break;

                case 'int':
                    if (! is_numeric($this->{$field})) {
                        throw new ValidateException($this, $field);
                    }

                    $this->{$field} = (int)$this->{$field};

                    if ((isset($rule['min']) && $this->{$field} < $rule['min']) ||
                        (isset($rule['max']) && $this->{$field} > $rule['max'])) {
                        throw new ValidateException($this, $field);
                    }

                    break;

                case 'float':
                    if (! is_numeric($this->{$field})) {
                        throw new ValidateException($this, $field);
                    }

                    $this->{$field} = (float)$this->{$field};

                    if ((isset($rule['min']) && $this->{$field} < $rule['min']) ||
                        (isset($rule['max']) && $this->{$field} > $rule['max'])) {
                        throw new ValidateException($this, $field);
                    }

                    break;

                case 'string':
                    $this->{$field} = trim($this->{$field});
                    $len = mb_strlen($this->{$field});

                    if ((isset($rule['min']) && $len < $rule['min']) || (isset($rule['max']) && $len > $rule['max'])) {
                        throw new ValidateException($this, $field);
                    }

                    break;

                case 'date':
                    $val = is_numeric($this->{$field}) ? (int)$this->{$field} : strtotime($this->{$field});
                    if (empty($val) || $val < 0) {
                        throw new ValidateException($this, $field);
                    }

                    $this->{$field} = date($rule['format'] ?? 'Y-m-d H:i:s', $val);

                    break;

                case 'set':
                    $vals = (array)($rule['vals'] ?? []);
                    if (empty($vals)) {
                        throw new ValidateException($this, $field);
                    }

                    if (! in_array($this->{$field}, $vals, false)) {
                        throw new ValidateException($this, $field);
                    }

                    break;

                default:
                    throw new InvalidArgumentException('invalid type: ' . $type);
            }
        }
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
     * Загрузка данных (только аттрибуты из attributes())
     *
     * @param array $data
     * @param string $formName
     * @param bool $skipEmpty пропускать пустые поля
     * @throws \ReflectionException
     */
    public function load(array $data, string $formName = null, bool $skipEmpty = true)
    {
        if (! isset($formName)) {
            $formName = static::formName();
        }

        // подстраиваем форму
        if ($formName !== '') {
            $data = $data[$formName] ?? [];
        }

        // выбираем значения для конфига
        $config = [];

        foreach (array_keys(static::attributes()) as $attr) {
            if (array_key_exists($attr, $data)) {
                if ($skipEmpty && ($data[$attr] === null || $data[$attr] === '')) {
                    continue;
                }

                $config[$attr] = $data[$attr];
            }
        }

        if (! empty($config)) {
            $this->configure($config);
        }
    }

    /**
     * Имя формы.
     *
     * @return string
     */
    public static function formName()
    {
        if (! isset(self::$_formName)) {
            $path = explode('\\', static::class);
            self::$_formName = array_pop($path);
        }

        return self::$_formName;
    }

    /**
     * Возвращает список полей модели для загрузки load и выгрузки toArray
     *
     * @return array [ name => text ]
     * @throws \ReflectionException
     */
    public static function attributes()
    {
        if (! isset(self::$_attributes)) {
            $ref = new ReflectionClass(static::class);

            $attrs = array_map(static function(ReflectionProperty $prop) {
                return $prop->name;
            }, $ref->getProperties(ReflectionProperty::IS_PUBLIC));

            self::$_attributes = array_combine($attrs, $attrs);
        }

        return self::$_attributes;
    }

    /**
     * Конвертирование в массив (только из списка аттрибутов).
     *
     * @return array
     * @throws \ReflectionException
     */
    public function toArray()
    {
        $arr = [];

        foreach (self::attributes() as $attr) {
            if (isset($this->{$attr})) {
                $arr[$attr] = $this->{$attr};
            }
        }

        return $arr;
    }
}
