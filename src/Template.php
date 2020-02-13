<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 00:46:01
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Throwable;
use yii\base\InvalidConfigException;

/**
 * Темплейт для OpenCart с проксированием к Registry.
 */
class Template implements RegistryProps
{
    /** @var string расширение по-умолчанию */
    public const EXT_DEFAULT = '.tpl';

    /** все обращения к $this в темплейте перенаправляются к Registry */
    use RegistryProxy;

    /** @var string роут или полный путь файла */
    private $pathRoute;

    /** @var array переменные */
    private $vars;

    /**
     * Конструктор.
     *
     * @param string $pathRoute маршрут или полный путь файла
     * @param array $vars переменные шаблона
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(string $pathRoute, array $vars = [])
    {
        // проверка файла
        if (empty($pathRoute)) {
            throw new InvalidConfigException('routeFile');
        }

        $this->pathRoute = $pathRoute;

        $this->vars = $vars;
    }

    /**
     * Возвращаеть путь файла.
     *
     * @return string полный путь файла для выполнения
     */
    public function getFilePath()
    {
        $path = $this->pathRoute;

        // добавляем расширение
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (empty($ext)) {
            $path = rtrim($path, '/') . self::EXT_DEFAULT;
        }

        // проверяем путь на полный файл
        if (is_file($path)) {
            return $path;
        }

        // удаляем тему вначале
        $matches = null;
        if (preg_match('~^.+?/template/([^/]+/.+)$~uism', $path, $matches)) {
            $path = $matches[1];
        }

        // полный путь
        /** @noinspection PhpUndefinedConstantInspection */
        return rtrim(DIR_TEMPLATE, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Рендеринг темплейта в строку
     *
     * @return string
     */
    public function run()
    {
        // распаковываем данные
        extract($this->vars, EXTR_REFS | EXTR_SKIP);

        ob_start();
        ob_implicit_flush(0);

        try {
            /** @noinspection PhpIncludeInspection */
            require($this->getFilePath());
        } finally {
            $ret = ob_get_clean();
        }

        return $ret;
    }

    /**
     * омпилирует в текст
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return trim($this->run());
        } catch (Throwable $ex) {
            /** @noinspection PhpUndefinedConstantInspection */
            trigger_error(DEBUG ? (string)$ex : $ex->getMessage(), E_USER_ERROR);
        }

        return '';
    }

    /**
     * Рендерит темплейт.
     *
     * @param string $pathRoute маршрут или полный путь файла
     * @param array $vars переменные
     * @return string результат рендеринга
     * @throws \yii\base\InvalidConfigException
     */
    public static function render(string $pathRoute, array $vars = [])
    {
        return (string)(new static($pathRoute, $vars));
    }

    /**
     * Делает дамп данных
     *
     * @param string $var переменная
     */
    public function dump(string $var = null)
    {
        echo '<!--suppress HtmlDeprecatedTag --><xmp>';
        /** @noinspection ForgottenDebugOutputInspection */
        var_dump(isset($var) ? ($this->vars[$var] ?? null) : $this->vars);
        exit;
    }
}
