<?php
namespace dicr\oclib;

/** @noinspection SenselessProxyMethodInspection */

class Session extends \yii\web\Session
{
    /** @var array  */
    public $data = [];

    /**
     * Session constructor.
     *
     * @param string $session_id
     * @param string $key
     */
    public function __construct($session_id = '', $key = 'default')
    {
        parent::__construct();
    }

    /**
     * Инициализация.
     */
    public function init()
    {
        parent::init();

        $this->open();

        $this->data = &$_SESSION;
    }

    /**
     * Запуск сессии.
     *
     * @return true
     */
    public function start()
    {
        $this->open();

        return true;
    }

    /**
     * Удаление сессии.
     *
     * @return true
     */
    public function destroy()
    {
        parent::destroy();

        return true;
    }
}

