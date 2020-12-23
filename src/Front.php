<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 19:09:09
 */

declare(strict_types = 1);

namespace dicr\oclib;

use RuntimeException;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\web\NotFoundHttpException;

use function array_pop;
use function class_exists;
use function constant;
use function explode;
use function implode;
use function is_file;
use function preg_match;
use function preg_replace;
use function strncmp;
use function ucfirst;

/**
 * Class Front
 */
class Front extends BaseObject
{
    /** @var Action[] */
    public $preActions = [];

    /** @var Action */
    public $errorAction;

    /**
     * @param Action $preAction
     */
    public function addPreAction(Action $preAction) : void
    {
        $this->preActions[] = $preAction;
    }

    /**
     * @param Action $action
     * @param ?Action $errorAction
     * @throws NotFoundHttpException
     */
    public function dispatch(Action $action, ?Action $errorAction = null) : void
    {
        if ($errorAction === null) {
            $errorAction = $this->errorAction;
        }

        // результат выполнения акции
        $res = null;

        try {
            // запускаем пред-акции
            foreach ($this->preActions as $preAction) {
                $result = $preAction->run();

                // если в результате получили акцию, то заменяем ей основную
                if ($result instanceof Action) {
                    $action = $result;
                    break;
                }
            }

            // пока возвращается акция
            while ($action) {
                $res = $action->run();
                $action = $res instanceof Action ? $res : null;
            }
        } catch (NotFoundHttpException $ex) {
            if ($errorAction !== null) {
                $res = $errorAction->run();
            } else {
                throw new RuntimeException('Акция ошибки не найдена', 0, $ex);
            }
        }

        if ($res instanceof \yii\base\Response) {
            // если вернули Response
            Yii::$app->response = $res;
        } elseif (is_scalar($res)) {
            // если вернули строковой результат, то добавляем его в output
            $res = (string)$res;
            if ($res !== '') {
                Yii::$app->response->content = $res;
            }
        }
    }

    /**
     * Создает акцию из маршрута.
     *
     * @param string $route
     * @param array $args
     * @return Action
     * @throws NotFoundHttpException
     */
    public static function createAction(string $route, array $args = []) : Action
    {
        // проверяем маршрут
        if (! preg_match('~^[a-z0-9_/]+$~u', $route)) {
            throw new NotFoundHttpException('route=' . $route);
        }

        $controllerPath = null;
        $method = null;

        // разбиваем маршрут на части
        $parts = (array)explode('/', $route);
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
            throw new NotFoundHttpException('route=' . $route);
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
            /** @var Controller $controller */
            $controller = new $class(implode('/', $controllerPath));
        } catch (Throwable $ex) {
            throw new NotFoundHttpException('class=' . $class, 0, $ex);
        }

        // привязываем аргументы
        $controller->actionParams = $args;

        // проверяем метод
        if ($method === null) {
            $method = $controller->defaultAction;
        } elseif (strncmp($method, '__', 2) === 0) {
            throw new NotFoundHttpException('method=' . $method);
        }

        // создаем акцию
        return new Action($method, $controller);
    }
}
