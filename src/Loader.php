<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 00:46:01
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\base\BaseObject;
use yii\base\Exception;
use function is_callable;

/**
 * Загрузчик OpenCart.
 */
class Loader extends BaseObject
{
    /**
     * Loader constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Реестр OpenCart.
     *
     * @return \dicr\oclib\Registry
     */
    protected function registry()
    {
        return Registry::app();
    }

    /**
     * Вызов контроллера.
     *
     * @param string $route
     * @param array $args аргументы контроллера
     * @return mixed
     */
    public function controller(string $route, $args = null)
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

        $class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', implode('/', $parts));
        $controller = new $class($this->registry());

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
     * @return object модель
     * @throws \yii\base\Exception
     */
    public function model(string $route, string $path = '')
    {
        $key = 'model_' . str_replace('/', '_', $route);
        $model = $this->registry()->get($key);
        if (! empty($model)) {
            return $model;
        }

        /** @noinspection PhpUndefinedConstantInspection */
        $file = ($path ?: DIR_APPLICATION . 'model/') . $route . '.php';

        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include_once($file);
            $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
            $model = new $class($this->registry());
            $this->registry()->set($key, $model);
        } else {
            throw new Exception('Error: Could not load model ' . $route . '!');
        }

        return $model;
    }

    /**
     * Загружает темплейт.
     *
     * @param string $route
     * @param array $data данные для темплейта
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function view(string $route, array $data = [])
    {
        return Template::render($route, $data);
    }

    /**
     * Загрузка библиотеки.
     *
     * @param string $route
     * @throws \yii\base\Exception
     */
    public function library(string $route)
    {
        // Sanitize the call
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route);

        /** @noinspection PhpUndefinedConstantInspection */
        $file = DIR_SYSTEM . 'library/' . $route . '.php';

        $class = str_replace('/', '\\', $route);
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include_once($file);
            $this->registry()->set(basename($route), new $class($this->registry()));
        } else {
            throw new Exception('Error: Could not load library ' . $route . '!');
        }
    }

    /**
     * @param $helper
     * @throws \yii\base\Exception
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function helper($helper)
    {
        /** @noinspection PhpUndefinedConstantInspection */
        $file = DIR_SYSTEM . 'helper/' . str_replace('../', '', (string)$helper) . '.php';

        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            include_once($file);
        } else {
            throw new Exception('Error: Could not load helper ' . $file . '!');
        }
    }

    /**
     * @param $config
     */
    public function config($config)
    {
        $this->registry()->get('config')->load($config);
    }

    /**
     * @param $language
     * @return mixed
     */
    public function language($language)
    {
        return $this->registry()->get('language')->load($language);
    }
}
