<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 27.09.20 19:28:11
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\base\BaseObject;
use yii\base\Exception;

use function is_callable;
use function is_file;
use function str_replace;

/**
 * Загрузчик OpenCart.
 */
class Loader extends BaseObject
{
    /**
     * Loader constructor.
     *
     * @noinspection PhpUnusedParameterInspection
     * @param ?Registry $registry
     */
    public function __construct(?Registry $registry = null)
    {
        parent::__construct();
    }

    /**
     * Реестр OpenCart.
     *
     * @return Registry
     */
    private static function registry() : Registry
    {
        return Registry::app();
    }

    /**
     * Вызов контроллера.
     *
     * @param string $route
     * @param ?array $args аргументы контроллера
     * @return mixed
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function controller(string $route, ?array $args = null)
    {
        if ($args === null) {
            $args = [];
        }

        $parts = explode('/', str_replace('../', '', $route));

        // Break apart the route
        while ($parts) {
            /** @noinspection PhpUndefinedConstantInspection */
            $file = DIR_APPLICATION . 'controller/' . implode('/', $parts) . '.php';
            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                include_once($file);
                break;
            }

            $method = array_pop($parts);
        }

        $class = 'Controller' .
            preg_replace('/[^a-zA-Z0-9]/', '', implode('/', $parts));

        $controller = new $class(static::registry());

        if (! isset($method)) {
            $method = 'index';
        }

        // Stop any magical methods being called
        if (strncmp($method, '__', 2) === 0) {
            return false;
        }

        $output = '';

        if (is_callable([$controller, $method])) {
            $output = $controller->$method($args);
        }

        return $output;
    }

    /**
     * Загрузка модели.
     *
     * @param string $route
     * @param string $path
     * @return Model модель
     * @throws Exception
     */
    public function model(string $route, string $path = '') : Model
    {
        $key = 'model_' . str_replace('/', '_', $route);

        // проверяем в кеше
        $model = static::registry()->get($key);
        if ($model === null) {
            /** @noinspection PhpUndefinedConstantInspection */
            $file = ($path ?: DIR_APPLICATION . 'model/') . $route . '.php';
            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                include_once($file);
                $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
                $model = new $class(static::registry());
                static::registry()->set($key, $model);
            } else {
                throw new Exception('Error: Could not load model ' . $route . '!');
            }
        }

        return $model;
    }

    /**
     * Загружает темплейт.
     *
     * @param string $route
     * @param array $data данные для темплейта
     * @return string
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function view(string $route, array $data = []) : string
    {
        return (string)(new Template($route, $data));
    }

    /**
     * Загрузка библиотеки.
     *
     * @param string $route
     * @throws Exception
     */
    public function library(string $route) : void
    {
        // Sanitize the call
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route);

        /** @noinspection PhpUndefinedConstantInspection */
        $file = DIR_SYSTEM . 'library/' . $route . '.php';
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include_once($file);

            $class = str_replace('/', '\\', $route);
            static::registry()->set(basename($route), new $class(static::registry()));
        } else {
            throw new Exception('Error: Could not load library ' . $route . '!');
        }
    }

    /**
     * @param $name
     * @throws Exception
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function helper(string $name) : void
    {
        /** @noinspection PhpUndefinedConstantInspection */
        $file = DIR_SYSTEM . 'helper/' . str_replace('../', '', $name) . '.php';

        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include_once($file);
        } else {
            throw new Exception('Error: Could not load helper ' . $file . '!');
        }
    }

    /**
     * @param $name
     * @return Config
     */
    public function config($name) : Config
    {
        /** @var Config $config */
        $config = static::registry()->get('config');
        $config->load($name);

        return $config;
    }

    /**
     * Загрузка языка
     *
     * @param string $route
     * @return Language
     */
    public function language(string $route) : Language
    {
        /** @var Language $language */
        $language = static::registry()->get('language');
        $language->load($route);

        return $language;
    }
}
