<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 27.09.20 18:09:42
 */

declare(strict_types = 1);
namespace dicr\oclib;

use ReflectionClass;
use ReflectionException;
use Yii;
use yii\base\Exception;

use function call_user_func_array;
use function count;

/**
 * Class Action.
 */
class Action
{
    /** @var string */
    private $id;

    /** @var string */
    private $route;

    /** @var string */
    private $method = 'index';

    /**
     * Action constructor.
     *
     * @param $route
     */
    public function __construct(string $route)
    {
        $this->id = $route;

        $parts = explode('/', preg_replace('/[^a-zA-Z0-9_\/]/', '', $route));

        // Break apart the route
        while ($parts) {
            $file = static::dirApplication() . '/controller/' . implode('/', $parts) . '.php';
            if (is_file($file)) {
                $this->route = implode('/', $parts);
                break;
            }

            $this->method = array_pop($parts);
        }
    }

    /**
     * Директория приложения.
     *
     * @return string
     */
    private static function dirApplication() : string
    {
        /** @noinspection PhpUndefinedConstantInspection */
        return rtrim(DIR_APPLICATION, '/');
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * @param ?Registry $registry
     * @param array $args
     * @return Exception|mixed
     * @throws ReflectionException
     */
    public function execute(?Registry $registry = null, array $args = [])
    {
        // Stop any magical methods being called
        if (strncmp($this->method, '__', 2) === 0) {
            return new Exception('Error: Calls to magic methods are not allowed!');
        }

        $file = static::dirApplication() . '/controller/' . $this->route . '.php';
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include_once($file);
            $class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $this->route);
            $controller = new $class($registry ?? Registry::app());
        } else {
            return new Exception('Error: Could not call ' . $this->route . '/' . $this->method . '!');
        }

        // инициализируем параметры Yii
        Yii::$app->requestedRoute = $this->id;
        Yii::$app->request->queryParams = Registry::app()->request->get;
        Yii::$app->controller = new \yii\web\Controller(Url::controllerByRoute($this->id), Yii::$app);

        // выполняем метод контроллера
        $reflection = new ReflectionClass($class);
        if ($reflection->hasMethod($this->method) &&
            $reflection->getMethod($this->method)->getNumberOfRequiredParameters() <= count($args)) {
            return call_user_func_array([$controller, $this->method], $args);
        }

        return new Exception('Error: Could not call ' . $this->route . '/' . $this->method . '!');
    }
}
