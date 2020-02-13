<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 00:46:01
 */

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\caching\CacheInterface;
use yii\di\Instance;

/**
 * Прокси кэша OpenCart на Yii.
 */
class Cache
{
    /** @var \yii\caching\CacheInterface */
    public $cache = 'cache';

    /** @var int ttl */
    protected $expire;

    /**
     * Constructor
     *
     * @param string $adaptor The type of storage for the cache.
     * @param int $expire Optional parameters
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($adaptor = null, $expire = null)
    {
        $this->expire = $expire;

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
    }

    /**
     * Возвращает данные по ключу.
     *
     * @param mixed $key
     * @return mixed|false
     */
    public function get($key)
    {
        return $this->cache->get($key);
    }

    /**
     * Сохраняет значение в кеше.
     *
     * @param mixed $key
     * @param mixed $val
     * @param int $ttl
     * @return bool
     */
    public function set($key, $val, int $ttl = null)
    {
        return $this->cache->set($key, $val, $ttl ?: $this->expire);
    }

    /**
     * Deletes a cache by key name.
     *
     * @param string $key The cache key
     * @return bool
     */
    public function delete($key)
    {
        return $this->cache->delete($key);
    }
}
