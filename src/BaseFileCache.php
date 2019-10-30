<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnused */

declare(strict_types = 1);
namespace dicr\oclib;

use RuntimeException;

/**
 * Файловый кэш.
 */
class BaseFileCache extends AbstractObject
{
    /** @var int время TTL по-умолчанию */
    public $ttl;

    /** @var float вероятность очистки кэша */
    public $gcProbability = 0.3;

    /**
     * Конструктор.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct([]);

        // очищаем кэш с заданной вероятностью
        $this->gc();
    }

    /**
     * Очищает старые файлы.
     *
     * @throws \Exception
     */
    public function gc()
    {
        if (random_int(1, 100) > $this->gcProbability * 100) {
            return;
        }

        $time = time();
        $files = $this->globFiles();
        foreach ($files as $file) {
            $matches = null;
            if ($matches[1] < $time && preg_match('~^[^.]+\.(\d+)$~um', pathinfo($file, PATHINFO_BASENAME))) {
                unlink($file);
            }
        }
    }

    /**
     * Читает файлы из кэша.
     *
     * @param mixed $key
     * @return string[]
     * @throws \Exception
     */
    protected function globFiles($key = null)
    {
        if (empty($key)) {
            $mask = '*';
        } else {
            $mask = self::cacheKey($key) . '.*';
        }

        /** @noinspection PhpUndefinedConstantInspection */
        $files = glob(DIR_CACHE . $mask, GLOB_NOSORT);
        if ($files === false) {
            /** @noinspection PhpUndefinedConstantInspection */
            throw new RuntimeException('Ошибка чтения кеша: ' . DIR_CACHE);
        }

        return $files;
    }

    /**
     * Возвращает ключ кэша.
     *
     * @param mixed $key
     * @return string
     */
    protected static function cacheKey($key)
    {
        return md5(serialize($key));
    }

    /**
     * Возвращает данные по ключу.
     *
     * @param mixed $key
     * @return mixed|false
     * @throws \Exception
     */
    public function get($key)
    {
        $files = $this->globFiles($key);
        $filename = reset($files);
        if (empty($filename)) {
            return false;
        }

        $data = file_get_contents($filename, false);
        if ($data === false) {
            /** @noinspection PhpUndefinedConstantInspection */
            throw new RuntimeException('Ошибка чтения из кэша: ' . DIR_CACHE);
        }

        return $this->decode($data);
    }

    /**
     * Декодирует данные
     *
     * @param string $data
     * @return mixed
     */
    protected function decode(string $data)
    {
        return unserialize($data, null);
    }

    /**
     * Сохраняет значение в кеше.
     *
     * @param mixed $key
     * @param mixed $val
     * @param int $ttl
     * @throws \Exception
     */
    public function set($key, $val, int $ttl = null)
    {
        /** @noinspection PhpUndefinedConstantInspection */
        $filename = DIR_CACHE . sprintf('%s.%d', self::cacheKey($key), (time() + $ttl) ?: $this->ttl);
        $val = $this->encode($val);

        if (file_put_contents($filename, $val, LOCK_EX) === false) {
            /** @noinspection PhpUndefinedConstantInspection */
            throw new RuntimeException('Ошибка сохранения файла в кеше: ' . DIR_CACHE);
        }
    }

    /**
     * Кодирует данные.
     *
     * @param mixed $data
     * @return string
     */
    protected function encode($data)
    {
        return serialize($data);
    }

    /**
     * Удалить значение из кэша.
     *
     * @param mixed $key
     * @throws \Exception
     */
    public function delete($key)
    {
        $files = $this->globFiles($key);
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Очищает весь кэш.
     *
     * @throws \Exception
     */
    public function clean()
    {
        foreach ($this->globFiles() as $file) {
            unlink($file);
        }
    }
}
