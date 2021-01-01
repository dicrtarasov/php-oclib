<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 01.01.21 10:43:03
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\web\NotFoundHttpException;

use function array_pop;
use function call_user_func_array;
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
 *
 * @property-read ?string $file
 * @property-read ?string $controllerPath
 * @property-read ?string $controllerClass
 * @property-read ?string $method
 */
class Action extends BaseObject
{
    /** @var string */
    public $route;

    /** @var ?array */
    public $args;

    /** @var bool анонсировать текущую акцию в Yii как главную запрошенную */
    public $populate = false;

    /** @var string|false файл контроллера */
    private $_file;

    /** @var array */
    private $_controllerPath;

    /** @var string метод контролера (рассчитывается при выполнении) */
    private $_method;

    /**
     * Action constructor.
     *
     * @param string $route
     * @param array $args
     * @param array $config
     */
    public function __construct(string $route, array $args = [], array $config = [])
    {
        $this->route = trim($route, '/');
        $this->args = $args;

        parent::__construct($config);
    }

    /**
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function execute()
    {
        // проверяем маршрут
        if (! preg_match('~^[a-z0-9_\-/]+$~ui', $this->route)) {
            throw new NotFoundHttpException('invalid route=' . $this->route);
        }

        // рассчитываем пути
        $this->resolve();

        $file = $this->file;
        $class = $this->controllerClass;
        $method = $this->method;

        // если не найден
        if (empty($file) || empty($class) || empty($method)) {
            throw new NotFoundHttpException($this->route);
        }

        // анонсируем текущую акцию
        if ($this->populate) {
            $this->populateYii();
        }

        // подключаем файл
        /** @noinspection PhpIncludeInspection */
        require_once $file;

        // проверяем наличие класса
        if (! class_exists($class)) {
            throw new NotFoundHttpException('Controller class: ' . $class);
        }

        // пытаемся создать контроллер
        try {
            $controller = new $class(Registry::app());
        } catch (Throwable $ex) {
            throw new NotFoundHttpException('Controller class: ' . $class, 0, $ex);
        }

        // проверяем метод
        if (strncmp($method, '__', 2) === 0) {
            throw new NotFoundHttpException('Controller method: ' . $method);
        }

        // проверяем наличие метода
        if (! method_exists($controller, $method)) {
            throw new NotFoundHttpException('Controller class: ' . $class . ', method=' . $method);
        }

        // выполняем метод контроллера
        return call_user_func_array([$controller, $method], $this->args ?: []);
    }

    /**
     * Файл контроллера.
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        $this->resolve();

        return $this->_file ?: null;
    }

    /**
     * Возвращает путь контроллера (рассчитывается во время исполнения).
     *
     * @return string|null
     */
    public function getControllerPath(): ?string
    {
        $this->resolve();

        return $this->_controllerPath ? implode('/', $this->_controllerPath) : null;
    }

    /**
     * Возвращает класс контроллера.
     *
     * @return string|null
     */
    public function getControllerClass(): ?string
    {
        $this->resolve();

        if (empty($this->_controllerPath)) {
            return null;
        }

        $class = 'Controller';
        foreach ($this->_controllerPath as $part) {
            $class .= ucfirst(preg_replace('~[^a-z0-9]+~ui', '', $part));
        }

        return $class;
    }

    /**
     * Метод контроллера.
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        $this->resolve();

        return $this->_method;
    }

    /**
     * Рассчитывает контроллер и путь.
     */
    private function resolve(): void
    {
        // уже разобрали ранее
        if ($this->_file === null) {
            $this->_file = false;
            $this->_controllerPath = null;
            $this->_method = null;

            // разбиваем маршрут на части
            $parts = (array)explode('/', $this->route);
            while (! empty($parts)) {
                $file = constant('DIR_APPLICATION') . 'controller/' . implode('/', $parts) . '.php';
                if (is_file($file)) {
                    $this->_file = $file;
                    $this->_controllerPath = $parts ?: null;
                    break;
                }

                $this->_method = array_pop($parts) ?: null;
            }

            // исправляем метод
            if (! empty($this->_file) && ! empty($this->_controllerPath) && empty($this->_method)) {
                $this->_method = 'index';
            }
        }
    }

    /**
     * Служебный метод анонсирования текущей акции в Yii как основной запрошенной.
     */
    private function populateYii(): void
    {
        Yii::$app->requestedRoute = $this->route;
        Yii::$app->requestedParams = $this->args;
        Yii::$app->controller = new \yii\web\Controller($this->controllerPath, Yii::$app);
        Yii::$app->requestedAction = new \yii\base\Action($this->method, Yii::$app->controller);
    }
}
