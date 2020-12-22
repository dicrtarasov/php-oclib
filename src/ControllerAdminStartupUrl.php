<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 03:21:30
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;

/**
 * Контроллер маршрутизации.
 * Предназначен для переопределения Action при старте обработки запроса.
 *
 * Нужно создать подкласс и разместить его в /admin/controller/startup/url.php, а также
 * добавить этот контроллер как preAction.
 */
class ControllerAdminStartupUrl extends Controller
{
    /**
     * Индекс.
     *
     * @return ?Action
     */
    public function index() : ?Action
    {
        // если маршрут задан непосредственно в параметре
        $route = (string)Yii::$app->request->get('route', '');
        if ($route !== '') {
            // если была переадресация с ЧПУ маршрута
            $route = (string)Yii::$app->request->get('_route_', '');
        }

        // очищаем параметры
        unset($this->request->get['route'], $this->request->get['_route_']);

        // возвращаем акцию
        return $route === '' ? null : new Action($route);
    }
}
