<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 22:47:53
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Прокси обращений объекта к OpenCart Registry.
 */
trait RegistryProxy
{
    /**
     * Реестр.
     *
     * @return Registry
     */
    private static function registry() : Registry
    {
        return Registry::app();
    }

    /**
     * Проверка наличия свойства в Registry.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        return static::registry()->has($name);
    }

    /**
     * Получить свойство из Registry.
     *
     * @param string $name
     * @return ?mixed
     */
    public function __get(string $name)
    {
        return static::registry()->get($name);
    }

    /**
     * Установить свойство в Registry.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value) : void
    {
        static::registry()->set($name, $value);
    }
}
