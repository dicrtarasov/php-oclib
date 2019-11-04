<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\base\BaseObject;
use yii\db\Exception;

/**
 * Загрузчик OpenCart.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class Loader extends BaseObject
{
    /** @var \dicr\oclib\Registry */
    private $registry;

    /**
     * Loader constructor.
     */
    public function __construct($registry = null)
    {
        parent::__construct([]);
    }

    /**
     * Инициализация.
     */
    public function init()
    {
        $this->registry = Registry::app();
    }

    /**
     * Вызов контроллера.
     *
     * @param string $route
     * @param mixed $args аргументы контроллера
     * @return bool|string|null
     */
    public function controller(string $route, ...$args)
    {
        $parts = explode('/', str_replace('../', '', (string)$route));

        // Break apart the route
        while ($parts) {
            $file = DIR_APPLICATION . 'controller/' . implode('/', $parts) . '.php';
            if (is_file($file)) {
                include_once($file);
                break;
            } else {
                $method = array_pop($parts);
            }
        }

        $class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', implode('/', $parts));
        $controller = new $class($this->registry);

        if (! isset($method)) {
            $method = 'index';
        }

        // Stop any magical methods being called
        if (substr($method, 0, 2) == '__') {
            return false;
        }

        $output = '';

        if (is_callable([$controller, $method])) {
            $output = call_user_func([$controller, $method], $args);
        }

        return $output;
    }

    /**
     * Загрузка модели.
     *
     * @param string $name
     * @return object модель
     * @throws \dicr\oclib\OcException
     */
    public function model(string $route, string $path = '')
    {
        $key = 'model_' . str_replace('/', '_', $route);
        $model = $this->registry->get($key);
        if (! empty($model)) {
            return $model;
        }

        $file = ($path ? $path : DIR_APPLICATION . 'model/') . $route . '.php';

        if (is_file($file)) {
            include_once($file);
            $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
            $model = new $class($this->registry);
            $this->registry->set($key, $model);
        } else {
            throw new OcException('Error: Could not load model ' . $route . '!');
        }

        return $model;
    }

    /**
     * Загружает темплейт.
     *
     * @param string $route
     * @param array $data данные для темплейта
     * @param string $path
     * @return \dicr\oclib\Template
     */
    public function view(string $route, array $data = [], string $path = '')
    {
        return new Template($route, $data);
    }

    /**
     * Загрузка библиотеки.
     *
     * @param string $route
     * @param array $config
     * @throws \Exception
     */
    public function library(string $route, $config = [])
    {
        // Sanitize the call
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);

        $file = DIR_SYSTEM . 'library/' . $route . '.php';

        $class = str_replace('/', '\\', $route);
        if (is_file($file)) {
            include_once($file);
            $this->registry->set(basename($route), new $class($this->registry));
        } else {
            throw new \Exception('Error: Could not load library ' . $route . '!');
        }
    }

    /**
     * @param $helper
     * @throws \yii\db\Exception
     */
    public function helper($helper)
    {
        $file = DIR_SYSTEM . 'helper/' . str_replace('../', '', (string)$helper) . '.php';

        if (file_exists($file)) {
            include_once($file);
        } else {
            throw new Exception('Error: Could not load helper ' . $file . '!');
        }
    }

    public function config($config)
    {
        $this->registry->get('config')->load($config);
    }

    public function language($language)
    {
        return $this->registry->get('language')->load($language);
    }
}
