<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 01.01.21 07:20:15
 */

declare(strict_types = 1);

namespace dicr\oclib;

use Yii;

/**
 * Контроллер маршрутизации.
 * Предназначен для переопределения Action при старте обработки запроса.
 *
 * Нужно создать подкласс и разместить его в /catalog/controller/startup/url.php, а также
 * добавить этот контроллер как preAction.
 */
class ControllerCatalogStartupUrl extends Controller
{
    /**
     * Декодирование ЧПУ.
     *
     * @return ?Action
     */
    public function index(): ?Action
    {
        // если маршрут задан непосредственно (/index.php?route=catalog/product)
        $route = (string)(Registry::$app->request->get['route'] ?? '');
        $params = [];

        if (empty($route)) {
            // если задан стандартный rewrite opencart
            if (! empty(Registry::$app->request->get['_route_'])) {
                // восстанавливаем pathInfo, которое opencart преобразовал в route
                Yii::$app->request->pathInfo = Registry::$app->request->get['_route_'];
            }

            // используем стандартный UrlManager
            $result = Yii::$app->urlManager->parseRequest(Yii::$app->request);
            if ($result !== false) {
                [$route, $params] = $result;
            }
        }

        // принимаем путь в качестве ЧПУ
        if (empty($route)) {
            $route = (string)Yii::$app->request->pathInfo;
        }

        // если имеются дополнительные параметры, то помещаем их в $_GET
        if (! empty($params)) {
            Registry::$app->request->get = array_merge(Registry::$app->request->get, $params);
        }

        // очищаем служебные параметры уже после объединения
        unset($this->request->get['route'], $this->request->get['_route_']);

        // передаем Yii новые параметры GET
        Yii::$app->request->queryParams = Registry::$app->request->get;

        // возвращаем новую акцию
        return empty($route) ? null : new Action($route);
    }
}
