<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Throwable;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

/**
 * Темплейт для OpenCart.
 */
class Template extends BaseObject implements RegistryProps
{
    /** все обращения к $this в темплейте перенаправляются к Registry */
    use RegistryProxy;

    /** @var string роут или полный путь файла */
    public $route;

    /** @var array переменные */
    public $vars = [];

    /** @var string расширение по-умолчанию */
    public $extDefault = '.tpl';

    /**
     * Конструктор.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        // проверка файла
        if (empty($this->route)) {
            throw new InvalidConfigException('route');
        }
    }

    /**
     * Возвращаеть путь файла.
     *
     * @return string полный путь файла для выполнения
     */
    public function getFilePath()
    {
        $path = $this->route;

        // добавляем расширение
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (empty($ext)) {
            $path = rtrim($path, '/') . $this->extDefault;
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
    public function render()
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
            return trim($this->render());
        } catch (Throwable $ex) {
            /** @noinspection PhpUndefinedConstantInspection */
            trigger_error(DEBUG ? (string)$ex : $ex->getMessage(), E_USER_ERROR);
        }

        return '';
    }

    /**
     * Рендерит темплейт.
     *
     * @param string $fileRoute маршрут или полный путь файла
     * @param array $vars переменные
     * @return string результат рендеринга
     */
    public static function run(string $fileRoute, array $vars = [])
    {
        return (string)(new static([
            'route' => $fileRoute,
            'vars' => $vars
        ]));
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
