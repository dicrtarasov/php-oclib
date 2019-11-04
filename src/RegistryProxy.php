<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Объект, проксирующий обращение к свойсвам на Registry.
 *
 * @package dicr\oclib
 * @property-read \dicr\oclib\BaseDB $db
 * @property-read \dicr\oclib\BaseLoader $load
 * @property-read \dicr\oclib\BaseUrl $url
 * @property-read \Request $request
 * @property-read \Response $response
 * @property-read \Document $document
 *
 */
abstract class RegistryProxy extends AbstractObject
{
    /**
     * Проверка наличия свойства в Registry.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        return BaseRegistry::app()->has($name);
    }

    /**
     * Получить свойство из Registry.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return BaseRegistry::app()->get($name);
    }

    /**
     * Установить свойство в Registry.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        BaseRegistry::app()->set($name, $value);
    }
}
