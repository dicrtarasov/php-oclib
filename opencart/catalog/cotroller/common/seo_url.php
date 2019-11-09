<?php

use app\components\CatalogUrlRule;
use yii\web\UrlNormalizerRedirectException;

class ControllerCommonSeoUrl extends Controller
{
    /**
     * Декодирование ЧПУ.
     *
     * @return \Action
     * @throws \yii\base\ExitException
     * @throws \yii\console\Exception
     * @throws \yii\web\NotFoundHttpException
     */
    public function index()
    {
        \Yii::$app->defaultRoute = 'common/home';

        $route = null;

        if (!empty($this->request->get['route'])) {
            $route = $this->request->get['route'];
        } else {
            try {
                [$route, $params] = Yii::$app->request->resolve();
            } catch (UrlNormalizerRedirectException $e) {
                $url = $e->url;
                if (is_array($url)) {
                    if (isset($url[0])) {
                        // ensure the route is absolute
                        $url[0] = '/' . ltrim($url[0], '/');
                    }

                    $url += Yii::$app->request->getQueryParams();
                }

                $urlManager = Yii::$app->getUrlManager();

                $response = Yii::$app->getResponse()->redirect($e->scheme ? $urlManager->createAbsoluteUrl($url) :
                    $urlManager->createUrl($url), $e->statusCode);

                Yii::$app->end(0, $response);
            }

            if (!empty($route)) {
                $this->request->get = $params;
            }
        }

        if (!empty($route)) {
            \Yii::$app->requestedRoute = $route;
            unset($this->request->get['route'], $this->request->get['_route_']);
            \Yii::$app->request->queryParams = $this->request->get;
            return new Action($route);
        }

        return null;
    }
}
