<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use function is_callable;

/**
 * Загрузчик.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class BaseLoader extends AbstractObject
{
    /**
     * Loader constructor.
     */
    public function __construct()
    {
        parent::__construct([]);
    }

    /**
     * Вызов контроллера.
     *
     * @param string $route
     * @param mixed $args аргументы контроллера
     * @return bool|string|null
     */
    public function controller(string $route, $args = null)
    {
        $output = null;

        $class = null;
        $parts = explode('/', str_replace('../', '', $route));
        // Break apart the route
        while ($parts) {
            /** @noinspection PhpUndefinedConstantInspection */
            $file = DIR_APPLICATION . 'controller/' . implode('/', $parts) . '.php';
            $class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', implode('/', $parts));

            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                include_once($file);
                break;
            }

            $method = array_pop($parts);
        }

        if ($class !== null) {
            $controller = new $class();

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
        }

        return $output;
    }

    /**
     * Загрузка модели.
     *
     * @param string $name
     * @return \dicr\oclib\BaseModel модель
     */
    public function model(string $name)
    {
        $name = str_replace('../', '', $name);
        $key = 'model_' . str_replace('/', '_', $name);

        // проверяем уже загруженную модель
        $model = BaseRegistry::app()->get($key);
        if (! empty($model)) {
            return $model;
        }

        /** @noinspection PhpUndefinedConstantInspection */
        $file = DIR_APPLICATION . 'model/' . $name . '.php';
        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            include_once($file);
            $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $name);
            $registry = BaseRegistry::app();
            $model = new $class($registry);
            $registry->set($key, $model);
        } else {
            trigger_error('Error: Could not load model ' . $file . '!');
        }

        return $model;
    }

    /**
     * Загружает темплейт.
     *
     * @param string $file относительный файл темплейа
     * @param array $data данные для темплейта
     * @return \dicr\oclib\BaseTemplate
     */
    public function view(string $file, array $data = [])
    {
        return new BaseTemplate($file, $data);
    }

    public function helper($helper)
    {
        /** @noinspection PhpUndefinedConstantInspection */
        $file = DIR_SYSTEM . 'helper/' . str_replace('../', '', (string)$helper) . '.php';

        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            include_once($file);
        } else {
            trigger_error('Error: Could not load helper ' . $file . '!');
            exit();
        }
    }

    public function config($config)
    {
        BaseRegistry::app()->get('config')->load($config);
    }

    public function language($language)
    {
        return BaseRegistry::app()->get('language')->load($language);
    }
}
