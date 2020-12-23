<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 18:17:20
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Прокси обращений объекта к OpenCart Registry.
 */
trait RegistryProxy
{
    /**
     * Проверка наличия свойства в Registry.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name) : bool
    {
        return Registry::app()->has($name);
    }

    /**
     * Получить свойство из Registry.
     *
     * @param string $name
     * @return ?mixed
     */
    public function __get(string $name)
    {
        return Registry::app()->get($name);
    }

    /**
     * Установить свойство в Registry.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value) : void
    {
        Registry::app()->set($name, $value);
    }
}
