<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 24.12.20 05:51:39
 */

declare(strict_types = 1);
namespace dicr\oclib;

use function array_merge;
use function is_file;
use function substr;

/**
 * Class Language
 *
 * Требует константу DIR_LANGUAGE.
 */
class Language
{
    /** @var string базовый язык */
    public const LANG_BASE = 'english';

    /** @var string перевод по-умолчанию */
    public const LANG_DEFAULT = 'ru-ru';

    /** @var string */
    private $lang = self::LANG_DEFAULT;

    /** @var string[] */
    private $data = [];

    /**
     * Language constructor.
     *
     * @param ?string $lang
     */
    public function __construct(?string $lang = null)
    {
        if (! empty($lang)) {
            $this->lang = $lang;
        }
    }

    /**
     * Получить перевод.
     *
     * @param string $key
     * @return string
     */
    public function get(string $key) : string
    {
        return $this->data[$key] ?? $key;
    }

    /**
     * Установить перевод.
     *
     * @param string $key
     * @param string $value
     */
    public function set(string $key, string $value) : void
    {
        $this->data[$key] = $value;
    }

    /**
     * Директория языка.
     *
     * @param string $lang
     * @return string
     */
    private static function langDir(string $lang) : string
    {
        /** @noinspection PhpUndefinedConstantInspection */
        return DIR_LANGUAGE . $lang;
    }

    /**
     * Загружает перевод маршрута.
     *
     * @param string $route
     * @param string $lang
     * @return string[]
     */
    private static function loadLang(string $route, string $lang) : array
    {
        $_ = [];

        $file = static::langDir($lang) . '/' . $route . '.php';
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include $file;
        } elseif (strncmp($route, 'extension/', 10) === 0) {
            // загружаем данные расширения
            $file = static::langDir($lang) . '/' . substr($route, 10) . '.php';
            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                include $file;
            }
        }

        return $_;
    }

    /**
     * Загрузка данных маршрута.
     *
     * @param string $route
     * @return string[]
     */
    public function load(string $route) : array
    {
        $this->data = array_merge($this->data, static::loadLang($route, 'english'));

        if ($this->lang !== 'english') {
            $this->data = array_merge($this->data, static::loadLang($route, $this->lang));
        }

        return $this->data;
    }
}
