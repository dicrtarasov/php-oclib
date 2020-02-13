<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 00:46:01
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
    public static function app()
    {
        return self::$_instance;
    }

    /*** Сандартные методы opencart *****************************************/

    /**
     * Получить значение.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->data[$key] ?? null;
    }

    /*** Волшебные методы ****************************************************/

    /**
     * Установить значение.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Проверить наличие.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Получить значение.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Установить значение.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Проверить наличие.
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }
}
