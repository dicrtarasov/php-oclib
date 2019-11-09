<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;
use yii\base\BaseObject;

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
     * @throws \yii\base\ExitException
     */
    public static function asJson($data)
    {
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = $data;

        return Yii::$app->end(0, $response);
    }
}
