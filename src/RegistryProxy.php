<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 28.12.20 21:00:26
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
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __isset($name) : bool
    {
        return $name === 'registry' || Registry::app()->has($name);
    }

    /**
     * Получить свойство из Registry.
     *
     * @param string $name
     * @return ?mixed
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __get($name)
    {
        return $name === 'registry' ? Registry::app() : Registry::app()->get($name);
    }

    /**
     * Установить свойство в Registry.
     *
     * @param string $name
     * @param mixed $value
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __set($name, $value) : void
    {
        if ($name !== 'registry') {
            Registry::app()->set($name, $value);
        }
    }
}
