<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnused */

declare(strict_types = 1);
namespace dicr\oclib;

/** @noinspection PhpUndefinedClassInspection */

/**
 * Конроллер.
 *
 * @property-read Url $url
 * @property-read DB $db
 * @property-read \Request $request
 * @property-read \Response $response
 */
abstract class Controller
{
    /**
     * Проверяет метод запроса POST.
     *
     * @return boolean
     */
    public static function isPost()
    {
        return (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
    }

    /**
     * Получить значение реестра.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return Registry::app()->get($key);
    }

    /**
     * Установить значение реестра.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value)
    {
        Registry::app()->set($key, $value);
    }

    /**
     * Проверка наличия параметра.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key)
    {
        return Registry::app()->has($key);
    }

    /**
     * Возвращает ответ как JSON.
     *
     * @param mixed $data
     * @return void
     */
    public function asJson($data)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->response->setOutput(Html::json($data));
        header('Content-Type: application/json; charset=UTF-8', true);
        exit;
    }
}
