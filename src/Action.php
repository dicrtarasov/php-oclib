<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 24.12.20 01:07:20
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Throwable;
use Yii;
use yii\web\NotFoundHttpException;

use function array_pop;
use function class_exists;
use function constant;
use function explode;
use function implode;
use function is_file;
use function method_exists;
use function preg_match;
use function preg_replace;
use function strncmp;
use function trim;
use function ucfirst;

/**
 * Class Action.
 */
class Action
{
    /** @var string */
    public $route;

    /** @var ?array */
    public $args;

    /**
     * @param string $route
     * @param array $args
     */
    public function __construct(string $route, array $args = [])
    {
        $this->route = trim($route, '/');
        $this->args = $args;
    }

    /**
     * @return mixed
     * @throws NotFoundHttpException
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function execute()
    {
        // проверяем маршрут
        if (! preg_match('~^[a-z0-9_\-/]+$~u', $this->route)) {
            throw new NotFoundHttpException('invalid route=' . $this->route);
        }

        $controllerPath = null;
        $method = null;

        // разбиваем маршрут на части
        $parts = (array)explode('/', $this->route);
        while (! empty($parts)) {
            $file = constant('DIR_APPLICATION') . 'controller/' . implode('/', $parts) . '.php';
            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                include_once($file);
                $controllerPath = $parts;
                break;
            }

            $method = array_pop($parts);
        }

        // если контроллер не найден
        if ($controllerPath === null) {
            throw new NotFoundHttpException('not found route=' . $this->route);
        }

        // строим класс
        $class = 'Controller';
        foreach ($controllerPath as $part) {
            $class .= ucfirst(preg_replace('~[^a-z0-9]+~', '', $part));
        }

        // проверяем наличие класса
        if (! class_exists($class)) {
            throw new NotFoundHttpException('class=' . $class);
        }

        // пытаемся создать контроллер
        try {
            $controller = new $class(Registry::app());
        } catch (Throwable $ex) {
            throw new NotFoundHttpException('class=' . $class, 0, $ex);
        }

        // проверяем метод
        if ($method === null) {
            $method = 'index';
        } elseif (strncmp($method, '__', 2) === 0) {
            throw new NotFoundHttpException('method=' . $method);
        }

        // проверяем наличие метода
        if (! method_exists($controller, $method)) {
            throw new NotFoundHttpException('class=' . $class . ', method=' . $method);
        }

        // устанавливаем маршрут в Yii
        Yii::$app->requestedRoute = $this->route;

        // сохраняем парамеры в Yii
        Yii::$app->request->queryParams = Registry::app()->request->get;

        // создаем контроллер Yii
        Yii::$app->controller = new \yii\web\Controller(implode('/', $controllerPath), Yii::$app);

        // выполняем метод контроллера
        return $controller->{$method}($this->args ?? []);
    }
}
