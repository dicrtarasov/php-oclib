<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\base\ArrayAccessTrait;
use yii\base\BaseObject;

/**
 * Прокси обращений объека к OpenCart Registry
 *
 * @package dicr\oclib
 * @property-read \dicr\oclib\Cache $cache
 * @property-read \dicr\oclib\DB $db
 * @property-read \dicr\oclib\Loader $load
 * @property-read \dicr\oclib\Url $url
 * @property-read \Request $request
 * @property-read \Response $response
 * @property-read \Document $document
 *
 */
abstract class RegistryProxy extends BaseObject
{
    use ArrayAccessTrait;

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
