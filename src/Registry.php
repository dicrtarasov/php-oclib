<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\base\BaseObject;

/**
 * Реестр.
 *
 * @property-read \dicr\oclib\Cache $cache
 * @property-read \dicr\oclib\DB $db
 * @property-read \dicr\oclib\Loader $load
 * @property-read \dicr\oclib\Url $url
 * @property-read \Config $config
 * @property-read \Request $request
 * @property-read \Response $response
 * @property-read \Session $session
 */
class Registry extends BaseObject
{
    /** @var self */
    private static $_instance;

    /** @var array данные */
    private $data = [];

    /**
     * Конструктор.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

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
     * @return bool
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Получить значение.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Установить значение.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Проверить наличие.
     *
     * @param string $key
     * @return boolean
     */
    public function __isset($key)
    {
        return $this->has($key);
    }
}
