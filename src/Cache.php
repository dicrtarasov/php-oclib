<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 19:00:07
 */

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\di\Instance;

/**
 * Прокси кэша OpenCart на Yii.
 */
class Cache
{
    /** @var CacheInterface */
    public $cache = 'cache';

    /** @var ?int ttl */
    protected $expire;

    /**
     * Constructor
     *
     * @param ?string $adaptor The type of storage for the cache.
     * @param ?int $expire Optional parameters
     * @throws InvalidConfigException
     */
    public function __construct(?string $adaptor = null, ?int $expire = null)
    {
        $this->expire = $expire;
        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
    }

    /**
     * Возвращает данные по ключу.
     *
     * @param string $key
     * @return mixed|false
     */
    public function get(string $key)
    {
        return $this->cache->get($key);
    }

    /**
     * Сохраняет значение в кеше.
     *
     * @param string $key
     * @param mixed $val
     * @param ?int $ttl
     */
    public function set(string $key, $val, int $ttl = null) : void
    {
        if (! $this->cache->set($key, $val, $ttl ?: $this->expire)) {
            Yii::warning('Ошибка установки кэша: ' . $key, __METHOD__);
        }
    }

    /**
     * Deletes a cache by key name.
     *
     * @param string $key The cache key
     */
    public function delete(string $key) : void
    {
        if (! $this->cache->delete($key)) {
            Yii::warning('Ошибка удаления кеша: ' . $key, __METHOD__);
        }
    }
}
