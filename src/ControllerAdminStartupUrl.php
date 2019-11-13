<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

namespace dicr\oclib;

use Action;
use Yii;

/**
 * Контроллер маршрутизации.
 * Предназначен для переопределеия Action при старте обработки запроса.
 *
 * Нужно создать подкласс и разместить его в /admin/controller/startup/url.php, а также
 * добавить этот контроллер как preAction.
 *
 * @package dicr\oclib
 */
class ControllerAdminStartupUrl extends Controller
{
    /**
     * Индекс.
     *
     * @return \Action
     * @throws \yii\base\ExitException
     */
    public function index()
    {
        // устанавливаем маршрут в Yii
        Yii::$app->requestedRoute = $this->resolveRoute();

        // очищаем параметры
        unset($this->request->get['route'], $this->request->get['_route_']);

        // сохраняем парамеры в Yii
        Yii::$app->request->queryParams = $this->request->get;

        // создаем конроллер Yii
        \Yii::$app->controller =
            new \yii\web\Controller(substr(Yii::$app->requestedRoute, strpos(Yii::$app->requestedRoute, '/')),
                \Yii::$app);

        // возвращаем действие
        return new Action(Yii::$app->requestedRoute);
    }

    /**
     * Возвращает маршрут.
     *
     * @return string
     */
    protected function resolveRoute()
    {
        Yii::$app->defaultRoute = 'common/dashboard';

        if (! empty($this->request->get['route'])) {
            return $this->request->get['route'];
        }

        if (! empty($this->request->get['_route_'])) {
            return $this->request->get['_route_'];
        }

        return Yii::$app->defaultRoute;
    }
}
