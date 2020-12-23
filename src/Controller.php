<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 19:18:51
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
 *
 * @property-read Request $request
 * @property-read Response $response
 */
abstract class Controller extends \yii\web\Controller implements RegistryProps
{
    use RegistryProxy;

    /**
     * Проверяет и устанавливает заголовки кэширования.
     *
     * @param int $id id объекта
     * @param string $modified дата изменения
     */
    protected static function ifModifiedSince(int $id, string $modified) : void
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
