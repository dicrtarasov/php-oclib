<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 22:43:29
 */

declare(strict_types = 1);

namespace dicr\oclib;

use Throwable;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\UrlNormalizerRedirectException;

use function is_array;

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
    public function index() : ?Action
    {
        // устанавливаем маршрут в Yii
        Yii::$app->requestedRoute = $this->resolveRoute();

        // очищаем параметры
        unset($this->request->get['route'], $this->request->get['_route_']);

        // сохраняем парамеры в Yii
        Yii::$app->request->queryParams = $this->request->get;

        // создаем контроллер Yii
        Yii::$app->controller = new \yii\web\Controller(Url::controllerByRoute(Yii::$app->requestedRoute), Yii::$app);

        // возвращаем действие
        return Yii::$app->requestedRoute !== Yii::$app->defaultRoute ? new Action(Yii::$app->requestedRoute) : null;
    }

    /**
     * Получение маршрута.
     *
     * @return string
     */
    protected function resolveRoute() : string
    {
        // маршрут Yii по-умолчанию
        Yii::$app->defaultRoute = 'common/home';

        // поддержка ссылок с прямым указанием маршрута, пример: /index.php?route=catalog/product
        if (! empty($this->request->get['route'])) {
            return $this->request->get['route'];
        }

        // поддержка prettyUrl-маршрутов и ЧПУ, переадресованных через .htaccess, пример: /catalog/product
        if (! empty($this->request->get['_route_'])) {
            // восстанавливаем путь переадресации
            Yii::$app->request->pathInfo = $this->request->get['_route_'];

            /** @var array|false $result */
            $result = false;

            // пытаемся разрешить как ЧПУ
            try {
                $result = Yii::$app->urlManager->parseRequest(Yii::$app->request);
            } /** @noinspection PhpRedundantCatchClauseInspection */
            catch (UrlNormalizerRedirectException $ex) {
                // переадресация от нормализатора Url
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
                try {
                    Yii::$app->end(0,
                        Yii::$app->response->redirect($ex->scheme ?
                            Yii::$app->urlManager->createAbsoluteUrl($url) :
                            Yii::$app->urlManager->createUrl($url), $ex->statusCode
                        )
                    );
                } catch (Throwable $ex) {
                    Yii::error($ex, __METHOD__);
                    exit;
                }
            }

            // если ЧПУ решен
            /** @noinspection OffsetOperationsInspection */
            if (is_array($result) && ! empty($result[0])) {
                // добавляем парамеры ЧПУ в параметры запроса
                /** @noinspection OffsetOperationsInspection */
                $this->request->get = ArrayHelper::merge($this->request->get, $result[1] ?? []);

                // возвращаем полученный из ЧПУ маршрут

                /** @noinspection OffsetOperationsInspection */
                return $result[0];
            }

            // возвращаем путь как маршрут, например /catalog/product
            return $this->request->get['_route_'];
        }

        // маршрут по-умолчанию
        return Yii::$app->defaultRoute;
    }
}
