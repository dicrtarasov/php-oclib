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
use yii\helpers\ArrayHelper;
use yii\web\UrlNormalizerRedirectException;
use function is_array;

/**
 * Контроллер маршрутизации.
 * Предназначен для переопределеия Action при старте обработки запроса.
 *
 * Нужно создать подкласс и разместить его в /catalog/controller/startup/url.php, а также
 * добавить этот контроллер как preAction.
 *
 * @package dicr\oclib
 */
class ControllerCatalogStartupUrl extends Controller
{
    /**
     * Декодирование ЧПУ.
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
        Yii::$app->controller = new \yii\web\Controller(Url::controllerByRoute(Yii::$app->requestedRoute), Yii::$app);

        // возвращаем действие
        return new Action(Yii::$app->requestedRoute);
    }

    /**
     * Получение маршрута.
     *
     * @return string
     * @throws \yii\base\ExitException
     */
    protected function resolveRoute()
    {
        // маршрут Yii по-умолчанию
        Yii::$app->defaultRoute = 'common/home';

        // поддержка ссылок с прямым указанием маршрута, пример: /index.php?route=catalog/product
        if (! empty($this->request->get['route'])) {
            return $this->request->get['route'];
        }

        // поддержка prettyUrl-маршрутов и ЧПУ, переадресаванных через .htaccess, пример: /catalog/product
        if (! empty($this->request->get['_route_'])) {
            // восстанавливаем путь переадресации
            Yii::$app->request->pathInfo = $this->request->get['_route_'];

            // пытаемся резолвить как ЧПУ
            try {
                $result = Yii::$app->urlManager->parseRequest(Yii::$app->request);
            } catch (UrlNormalizerRedirectException $ex) {
                // переадресация от нормализаора Url
                $url = $ex->url;

                // сроим ссылку переадресации
                if (is_array($url)) {
                    if (isset($url[0])) {
                        // ensure the route is absolute
                        $url[0] = '/' . ltrim($url[0], '/');
                    }

                    if (! empty(Yii::$app->request->queryParams)) {
                        $url .= '?' . \dicr\helper\Url::buildQuery(Yii::$app->request->queryParams);
                    }
                }

                // делаем переадресацию
                Yii::$app->end(0,
                    Yii::$app->response->redirect($ex->scheme ? Yii::$app->urlManager->createAbsoluteUrl($url) :
                        Yii::$app->urlManager->createUrl($url), $ex->statusCode));
            }

            // если ЧПУ решен
            if (! empty($result) && ! empty($result[0])) {
                // добавляем парамеры ЧПУ в параметры запроса
                $this->request->get = ArrayHelper::merge($this->request->get, $result[1]);

                // возвращаем полученный из ЧПУ маршрут
                return $result[0];
            }

            // возвращаем путь как маршрут, например /catalog/product
            return $this->request->get['_route_'];
        }

        // маршрут по-умолчанию
        return Yii::$app->defaultRoute;
    }
}
