<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 27.09.20 17:11:02
 */

declare(strict_types = 1);
namespace dicr\oclib;

use function extract;
use function ob_implicit_flush;
use function ob_start;
use function preg_match;
use function rtrim;
use function trim;

use const EXTR_REFS;
use const EXTR_SKIP;

/**
 * Темплейт для OpenCart с проксированием к Registry.
 * Требует DIR_TEMPLATE.
 *
 * @property-read string $route
 * @property-read array $vars
 * @property-read string $filePath
 */
class Template extends Widget
{
    /** все обращения к $this в темплейте перенаправляются к Registry */
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
        parent::__construct();

        // удаляем тему вначале
        $matches = null;
        if (preg_match('~^(.+?/)?template/([^/]+/.+)$~uism', $route, $matches)) {
            $route = $matches[2];
        }

        $this->_route = trim($route, '/');

        $this->_vars = $vars;
    }

    /**
     * Маршрут
     *
     * @return string
     */
    public function getRoute() : string
    {
        return $this->_route;
    }

    /**
     * Параметры.
     *
     * @return array
     */
    public function getVars() : array
    {
        return $this->_vars;
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
    public function getFilePath() : string
    {
        if ($this->_filePath === null) {
            $this->_filePath = $this->_route;

            // добавляем расширение
            $ext = pathinfo($this->_filePath, PATHINFO_EXTENSION);
            if (empty($ext)) {
                $this->_filePath .= '.' . self::EXT_DEFAULT;
            }

            $this->_filePath = rtrim(static::dirTemplate(), '/') . '/' . $this->_filePath;
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
            require($this->filePath);
        };

        try {
            ob_start();
            ob_implicit_flush(0);
            $run();
        } finally {
            $ret = ob_get_clean();
        }

        return $ret;
    }
}
