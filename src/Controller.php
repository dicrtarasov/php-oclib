<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnused */

declare(strict_types = 1);
namespace dicr\oclib;

/** @noinspection PhpUndefinedClassInspection */

/**
 * Конроллер OpenCart.
 */
abstract class Controller extends RegistryProxy
{
    /**
     * BaseController constructor.
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
    public function isPost()
    {
        return (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
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
        $this->response->setOutput(Html::json($data));
        header('Content-Type: application/json; charset=UTF-8', true);
        exit;
    }
}
