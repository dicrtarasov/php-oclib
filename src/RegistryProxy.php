<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 20:03:42
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Прокси обращений объекта к OpenCart Registry.
 */
abstract class RegistryProxy implements RegistryProps
{
    /** Registry */
    protected $registry;

    /**
     * RegistryProxy constructor.
     *
     * @param ?Registry $registry
     */
    public function __construct(?Registry $registry = null)
    {
        $this->registry = $registry ?: Registry::app();
    }

    /**
     * Проверка наличия свойства в Registry.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name) : bool
    {
        return $this->registry->has($name);
    }

    /**
     * Получить свойство из Registry.
     *
     * @param string $name
     * @return ?mixed
     */
    public function __get(string $name)
    {
        return $this->registry->get($name);
    }

    /**
     * Установить свойство в Registry.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value) : void
    {
        $this->registry->set($name, $value);
    }
}
