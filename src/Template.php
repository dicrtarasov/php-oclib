<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use InvalidArgumentException;
use Throwable;

/**
 * Темплейт.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2016
 */
class Template extends ArrayObject
{
    /** @var string файл */
    private $_file;

    /** @var array переменные */
    private $_data;

    /**
     * Конструктор.
     *
     * @param string $file
     * @param array $data
     */
    public function __construct(string $file, array $data = [])
    {
        parent::__construct([]);

        // проверяем аргумент
        if (empty($file)) {
            throw new InvalidArgumentException('file');
        }

        $this->_file = $file;
        $this->_data = $data;
    }

    /**
     * Проверка наличия переменной.
     *
     * @param string $key
     * @return boolean
     */
    public function __isset($key)
    {
        return Registry::app()->has($key);
    }

    /**
     * Возвращает значение переменной
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return Registry::app()->get($key);
    }

    /**
     * Устанавливает значение переменной.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }

    /**
     * омпилирует в текст
     *
     * @return string
     */
    public function __toString()
    {
        $ret = '';

        try {
            $ret = $this->render();
        } catch (Throwable $ex) {
            /** @noinspection PhpUndefinedConstantInspection */
            trigger_error(DEBUG ? $ex : $ex->getMessage(), E_USER_ERROR);
        }

        return $ret;
    }

    /**
     * Рендеринг темплейта в строку
     *
     * @return string
     */
    public function render()
    {
        // распаковываем данные
        extract($this->_data, EXTR_REFS | EXTR_SKIP);

        // выполняем файл
        ob_start();
        /** @noinspection PhpIncludeInspection */
        require($this->getFilePath());
        return ob_get_clean();
    }

    /**
     * Возвращаеть путь файла.
     *
     * @return string полный путь файла для выполнения
     */
    protected function getFilePath()
    {
        $path = $this->_file;

        // удаляем тему вначале
        $matches = null;
        if (preg_match('~^.+?/template/([^/]+/.+)$~uism', $path, $matches)) {
            $path = $matches[1];
        }

        // полный путь
        /** @noinspection PhpUndefinedConstantInspection */
        $path = rtrim(DIR_TEMPLATE, '/') . '/' . ltrim($path, '/');

        // добавляем расширение
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (empty($ext)) {
            $path .= '.tpl';
        }

        return $path;
    }

    /**
     * Делает дамп данных
     *
     * @param null $var
     */
    public function dump($var = null)
    {
        echo '<!--suppress HtmlDeprecatedTag --><xmp>';
        /** @noinspection ForgottenDebugOutputInspection */
        var_dump($this->_data[$var] ?? null);
        exit;
    }
}
