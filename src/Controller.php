<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types = 1);
namespace dicr\oclib;

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
        return (strtoupper($_SERVER['REQUEST_METHOD'] ?? null) === 'POST');
    }

    /**
     * Возвращает ответ как JSON.
     *
     * @param mixed $data
     * @return void
     */
    public function asJson($data)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        header('Content-Type: application/json; charset=UTF-8', true);
        $this->response->setOutput(Html::json($data));
        exit;
    }
}
