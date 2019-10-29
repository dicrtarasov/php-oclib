<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Реестр.
 *
 * @property-read DB $db
 * @property-read Loader $load
 * @property-read \Config $config
 * @property-read \Url $url
 * @property-read \Request $request
 * @property-read \Response $response
 * @property-read \Cache $cache
 * @property-read \Session $session
 */
class Registry
{
    /** @var self */
    private static $_instance;

    /** @var array данные */
    private $data = [];

    /**
     * Конструктор
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
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Установить значение.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value)
    {
        $this->set($key, $value);
    }

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
     * @return boolean
     */
    public function __isset(string $key)
    {
        return $this->has($key);
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
}
