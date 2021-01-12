<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 12.01.21 17:20:49
 */

declare(strict_types = 1);

namespace dicr\oclib;

use InvalidArgumentException;
use Throwable;
use Yii;

use function gmdate;
use function header;
use function in_array;
use function md5;
use function sprintf;
use function strtotime;

/**
 * Контроллер OpenCart.
 */
abstract class Controller implements RegistryProps
{
    use RegistryProxy;

    /**
     * Рендерит темплейт.
     *
     * @param string $route
     * @param array $params
     * @noinspection PhpMethodMayBeStaticInspection
     * @return string
     */
    public function render(string $route, array $params = []) : string
    {
        return Template::render($route, $params);
    }

    /**
     * Возвращает ответ как JSON.
     *
     * @param mixed $data
     * @noinspection PhpMethodMayBeStaticInspection
     * @return \yii\web\Response
     */
    public function asJson($data) : \yii\web\Response
    {
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = $data;

        return $response;
    }

    /**
     * Переадресация на URL.
     *
     * @param string|array $url
     * @param int $code
     * @return \yii\web\Response
     */
    public function redirect($url, int $code = 303): \yii\web\Response
    {
        return Yii::$app->response->redirect($url, $code);
    }

    /**
     * Проверяет и устанавливает заголовки кэширования.
     *
     * @param int $id id объекта
     * @param string $modified дата изменения
     */
    public static function ifModifiedSince(int $id, string $modified) : void
    {
        if ($id < 1) {
            throw new InvalidArgumentException('id');
        }

        $timestamp = strtotime($modified);
        if ($timestamp <= 0) {
            throw new InvalidArgumentException('modified');
        }

        $etag = md5($id . $timestamp);

        // удаляем заголовки, которые устанавливает php session
        header_remove('Cache-Control');
        header_remove('Pragma');
        header_remove('Expires');

        // устанавливаем заголовки
        header(sprintf('ETag: "%s"', $etag));
        header('Last-Modified: ' . gmdate('r', $timestamp));
        header('Cache-Control: public');

        // проверяем заголовки запроса
        if (! Yii::$app->request->isGet && ! Yii::$app->request->isPost) {
            return;
        }

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        if ((Yii::$app->request->headers->has('If-None-Match') &&
                in_array('"' . $etag . '"', Yii::$app->request->getETags(), true)) ||
            (Yii::$app->request->headers->has('If-Modified-Since') &&
                @strtotime(Yii::$app->request->headers->get('If-Modified-Since')) >= $timestamp)) {
            $response = Yii::$app->response;
            $response->clear();
            $response->statusCode = 304;
            $response->statusText = 'Not Modified';
            try {
                Yii::$app->end(0, $response);
            } catch (Throwable $ex) {
                Yii::error($ex, __METHOD__);
                exit;
            }
        }
    }
}
