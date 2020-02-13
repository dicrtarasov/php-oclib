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
 * Прокси обращений объекта к OpenCart Registry.
 *
 * @package dicr\oclib
 */
trait RegistryProxy
{
    /**
     * Проверка наличия свойства в Registry.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return Registry::app()->has($name);
    }

    /**
     * Получить свойство из Registry.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return Registry::app()->get($name);
    }

    /**
     * Установить свойство в Registry.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        Registry::app()->set($name, $value);
    }
}
