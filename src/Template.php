<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 21:30:57
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Throwable;
use Yii;

use function extract;
use function ob_implicit_flush;
use function ob_start;
use function preg_match;
use function rtrim;

use const EXTR_REFS;
use const EXTR_SKIP;

/**
 * Темплейт для OpenCart с проксированием к Registry.
 *
 * Требует DIR_TEMPLATE
 */
class Template implements RegistryProps
{
    /** все обращения к $this в темплейте перенаправляются к Registry */
    use RegistryProxy;

    /** @var string расширение по-умолчанию */
    public const EXT_DEFAULT = 'tpl';

    /** @var string маршрут или полный путь файла */
    private $route;

    /** @var array переменные */
    private $vars;

    /**
     * Конструктор.
     *
     * @param string $pathRoute маршрут или полный путь файла
     * @param array $vars переменные шаблона
     */
    public function __construct(string $pathRoute, array $vars = [])
    {
        // удаляем тему вначале
        $matches = null;
        if (preg_match('~^(.+?/)?template/([^/]+/.+)$~uism', $pathRoute, $matches)) {
            $pathRoute = $matches[2];
        }

        $this->route = trim($pathRoute, '/');
        $this->vars = $vars;
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
            $this->_filePath = $this->route;

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
            extract($this->vars, EXTR_REFS | EXTR_SKIP);

            /** @noinspection PhpIncludeInspection */
            require($this->filePath());
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

    /**
     * Компилирует в текст
     *
     * @return string
     */
    public function __toString() : string
    {
        try {
            return trim($this->run());
        } catch (Throwable $ex) {
            Yii::error($ex, __METHOD__);
            trigger_error(YII_DEBUG ? (string)$ex : $ex->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * Рендерит темплейт.
     *
     * @param string $pathRoute маршрут или полный путь файла
     * @param array $vars переменные
     * @return string результат рендеринга
     */
    public static function render(string $pathRoute, array $vars = []) : string
    {
        return (string)(new static($pathRoute, $vars));
    }
}
