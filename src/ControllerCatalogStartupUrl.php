<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 19:20:19
 */

declare(strict_types = 1);

namespace dicr\oclib;

use Throwable;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\UrlNormalizerRedirectException;

use function is_array;
use function ltrim;

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
     * @throws NotFoundHttpException
     */
    public function index() : ?Action
    {
        $route = $this->resolveRoute();

        // очищаем параметры
        unset($this->request->get['route'], $this->request->get['_route_']);

        // возвращаем действие
        return $route === null ? null : Front::createAction($route);
    }

    /**
     * Получение маршрута.
     *
     * @return ?string
     */
    protected function resolveRoute() : ?string
    {
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

        return null;
    }
}
