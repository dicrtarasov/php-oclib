<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 27.09.20 17:28:06
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
     * @inheritDoc
     * Проверка наличия свойства в Registry.
     *
     * @param string $name
     * @return bool
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __isset($name)
    {
        return static::registry()->has($name);
    }

    /**
     * @inheritDoc
     * Получить свойство из Registry.
     *
     * @param string $name
     * @return ?mixed
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __get($name)
    {
        return static::registry()->get($name);
    }

    /**
     * @inheritDoc
     * Установить свойство в Registry.
     *
     * @param string $name
     * @param mixed $value
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __set($name, $value) : void
    {
        static::registry()->set($name, $value);
    }
}
