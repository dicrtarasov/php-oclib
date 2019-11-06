<?php

namespace dicr\oclib;

/**
 * Запрос.
 *
 * @package dicr\oclib
 */
class Request extends \yii\web\Request
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
    public function init()
    {
        parent::init();

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
     * @param $data
     * @return array|string
     */
    public function clean($params)
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                unset($params[$key]);

                $params[$this->clean($key)] = $this->clean($value);
            }
        } else {
            $params = htmlspecialchars($params, ENT_COMPAT, 'UTF-8');
        }

        return $params;
    }
}
