<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 24.12.19 02:41:26
 */

/** @noinspection PhpUnusedParameterInspection */
declare(strict_types=1);

namespace dicr\oclib;

use Yii;
use yii\base\BaseObject;
use yii\base\ExitException;
use yii\base\InvalidArgumentException;
use function gmdate;
use function header;
use function md5;
use function ob_end_clean;
use function ob_get_level;
use function sprintf;
use function str_replace;
use function strtotime;

/**
 * Конроллер OpenCart.
 */
abstract class Controller extends BaseObject implements RegistryProps
{
    /** все обращения к $this в конроллере перенаправляюся к Registry */
    use RegistryProxy;

    /**
     * BaseController constructor.
     *
     * @param null $registry
     */
    public function __construct($registry = null)
    {
        parent::__construct([]);
    }

    /**
     * Проверяет метод запроса POST.
     *
     * @return boolean
     * @noinspection PhpUnused
     */
    public static function isPost()
    {
        return Yii::$app->request->isPost;
    }

    /**
     * Возвращает ответ как JSON.
     *
     * @param mixed $data
     * @return void
     * @throws ExitException
     * @noinspection PhpUnused
     */
    public static function asJson($data)
    {
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = $data;

        return Yii::$app->end(0, $response);
    }

    /**
     * Очищает выходной буфер.
     */
    public static function cleanOutput()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Проверяет и устанавливает заголовки кэшировния.
     *
     * @param int $id id объекта
     * @param string $modified дата изменения
     * @noinspection PhpUnused
     */
    protected static function ifModifiedSince(int $id, string $modified)
    {
        if ($id < 1) {
            throw new InvalidArgumentException('id');
        }

        $timestamp = strtotime($modified);
        if ($timestamp <= 0) {
            throw new InvalidArgumentException('modified');
        }

        $etag = md5($id . $timestamp);

        // устанавливаем заголовки
        header(sprintf('ETag: "%s"', $etag), true);
        header('Last-Modified: ' . gmdate('r', $timestamp));
        header('Cache-Control: public');

        // проверяем заголовки запроса
        if ((!empty($_SERVER['HTTP_IF_NONE_MATCH']) &&
                str_replace('"', '', $_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
            (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
                strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $timestamp)) {
            // очищаем буфер
            self::cleanOutput();

            // выходим как не измененный контент
            header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
            exit();
        }
    }
}
