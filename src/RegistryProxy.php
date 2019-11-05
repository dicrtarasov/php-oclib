<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
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
