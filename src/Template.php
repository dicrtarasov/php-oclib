<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 18:18:39
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Throwable;

use function extract;
use function ob_get_clean;
use function ob_start;
use function pathinfo;
use function preg_match;
use function rtrim;
use function trigger_error;
use function trim;

use const E_USER_ERROR;
use const EXTR_REFS;
use const EXTR_SKIP;
use const PATHINFO_EXTENSION;

/**
 * Темплейт для OpenCart с проксированием к Registry.
 * Требует DIR_TEMPLATE.
 */
class Template implements RegistryProps
{
    use RegistryProxy;

    /** @var string расширение по-умолчанию */
    public const EXT_DEFAULT = 'tpl';

    /** @var string маршрут или полный путь файла */
    private $_route;

    /** @var array переменные */
    private $_vars;

    /**
     * Конструктор.
     *
     * @param string $route маршрут или полный путь файла
     * @param array $vars переменные шаблона
     */
    public function __construct(string $route, array $vars = [])
    {
        // удаляем тему вначале
        $matches = null;
        if (preg_match('~^(.+?/)?template/([^/]+/.+)$~uism', $route, $matches)) {
            $route = $matches[2];
        }

        $this->_route = trim($route, '/');

        $this->_vars = $vars;
    }

    /**
     * Директория темплейтов.
     *
     * @return string
     */
    private static function dirTemplate() : string
    {
        /** @noinspection PhpUndefinedConstantInspection */
        return DIR_TEMPLATE;
    }

    /** @var string */
    private $_filePath;

    /**
     * Путь файла.
     *
     * @return string полный путь файла для выполнения
     */
    public function filePath() : string
    {
        if ($this->_filePath === null) {
            $path = $this->_route;

            // добавляем расширение
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (empty($ext)) {
                $path .= '.' . self::EXT_DEFAULT;
            }

            $this->_filePath = rtrim(static::dirTemplate(), '/') . '/' . $path;
        }

        return $this->_filePath;
    }

    /**
     * Рендеринг темплейта в строку
     *
     * @return string
     */
    public function run() : string
    {
        // функция для сокрытия локальных переменных
        $run = function () {
            // распаковываем данные
            extract($this->_vars, EXTR_REFS | EXTR_SKIP);

            /** @noinspection PhpIncludeInspection */
            require($this->filePath());
        };

        ob_start();

        try {
            $run();
        } finally {
            $ret = ob_get_clean();
        }

        return $ret;
    }

    /**
     * Конвертирует в строку.
     *
     * @return string
     */
    public function __toString() : string
    {
        try {
            return $this->run();
        } catch (Throwable $ex) {
            /** @noinspection PhpUndefinedConstantInspection */
            trigger_error(DEBUG ? (string)$ex : $ex->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * Рендерит шаблон.
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public static function render(string $route, array $params = []) : string
    {
        $tpl = new static($route, $params);

        return (string)$tpl;
    }
}
