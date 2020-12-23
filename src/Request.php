<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 20:03:42
 */

declare(strict_types = 1);
namespace dicr\oclib;

use function is_array;

/**
 * Запрос.
 */
class Request
{
    /** @var array */
    public $get;

    /** @var array */
    public $post;

    /** @var array */
    public $request;

    /** @var array */
    public $cookie;

    /** @var array */
    public $files;

    /** @var array */
    public $server;

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

    /**
     * Экранирование парамеров.
     *
     * @param string|array $params
     * @return array|string
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function clean($params)
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                unset($params[$key]);

                $params[(string)$this->clean($key)] = $this->clean($value);
            }
        } else {
            $params = htmlspecialchars($params, ENT_COMPAT);
        }

        return $params;
    }
}
