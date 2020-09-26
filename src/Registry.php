<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 19:49:57
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Реестр.
 */
class Registry implements RegistryProps
{
    /** @var self */
    private static $_instance;

    /** @var array данные */
    private $data = [];

    /**
     * Конструктор.
     *
     */
    public function __construct()
    {
        self::$_instance = $this;
    }

    /**
     * Возвращает экземпляр приложения.
     *
     * @return self
     */
    public static function app() : self
    {
        return self::$_instance;
    }

    /** Волшебные методы ****************************************************/

    /**
     * Получить значение.
     *
     * @param string $key
     * @return ?mixed
     */
    public function __get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Установить значение.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value) : void
    {
        if ($value === null) {
            unset($this->data[$key]);
        } else {
            $this->data[$key] = $value;
        }
    }

    /**
     * Проверить наличие.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key) : bool
    {
        return isset($this->data[$key]);
    }


    /** Стандартные методы opencart *****************************************/

    /**
     * Получить значение.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->__get($key);
    }

    /**
     * Установить значение.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value) : void
    {
        $this->__set($key, $value);
    }

    /**
     * Проверить наличие.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key) : bool
    {
        return $this->__isset($key);
    }
}
