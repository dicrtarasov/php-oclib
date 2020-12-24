<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 24.12.20 05:49:45
 */

declare(strict_types = 1);
namespace dicr\oclib;

use RuntimeException;

use function rtrim;

/**
 * Class Config
 */
class Config
{
    /** @var array */
    private $data = [];

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value) : void
    {
        if ($value === null) {
            unset($this->data[$key]);
        } else {
            $this->data[$key] = $value;
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key) : bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Директория конфигов.
     *
     * @return string
     */
    private static function dirConfig() : string
    {
        /** @noinspection PhpUndefinedConstantInspection */
        return DIR_CONFIG;
    }

    /**
     * @param string $filename
     */
    public function load(string $filename) : void
    {
        $file = rtrim(static::dirConfig(), '/') . '/' . $filename . '.php';
        if (file_exists($file)) {
            $_ = [];
            /** @noinspection PhpIncludeInspection */
            require $file;
            $this->data = array_merge($this->data, $_);
        } else {
            throw new RuntimeException('Error: Could not load config ' . $filename . '!');
        }
    }

    /**
     * @param string $key
     * @return ?mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param $value
     */
    public function __set(string $key, $value) : void
    {
        $this->set($key, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function __isset(string $key) : bool
    {
        return $this->has($key);
    }
}
