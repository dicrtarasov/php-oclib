<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use function is_array;

/**
 * Запрос.
 *
 * @package dicr\oclib
 */
class Request
{
    public $get = [];

    public $post = [];

    public $request = [];

    public $cookie = [];

    public $files = [];

    public $server = [];

    /**
     * Инициализация.
     */
    public function __construct()
    {
        $this->get = &$_GET;
        $this->post = &$_POST;
        $this->request = &$_REQUEST;
        $this->cookie = &$_COOKIE;
        $this->files = &$_FILES;
        $this->server = &$_SERVER;
    }

    /** @noinspection PhpMethodMayBeStaticInspection */

    /**
     * Экранирование парамеров.
     *
     * @param $params
     * @return array|string
     */
    public function clean($params)
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                unset($params[$key]);

                $params[(string)$this->clean($key)] = $this->clean($value);
            }
        } else {
            $params = htmlspecialchars($params, ENT_COMPAT, 'UTF-8');
        }

        return $params;
    }
}
