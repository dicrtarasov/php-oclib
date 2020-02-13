<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 00:46:01
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\di\Instance;

/**
 * Прокси сессии OpeCart на Yii.
 *
 * @package dicr\oclib
 */
class Session
{
    /** @var \yii\web\Session компонент Yii */
    public $session = 'session';

    /** @var array */
    public $data = [];

    /**
     * Session constructor.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct()
    {
        $this->session = Instance::ensure($this->session, \yii\web\Session::class);

        $this->start();

        $this->data = &$_SESSION;
    }

    /**
     * Запуск сессии.
     *
     * @return string ID сессии
     */
    public function start()
    {
        $this->session->open();

        return $this->getId();
    }

    /**
     * Возвращает ID сессии.
     *
     * @return string
     */
    public function getId()
    {
        return $this->session->id;
    }

    /**
     * Закрытие сессии.
     */
    public function close()
    {
        $this->session->close();
    }

    /**
     * Удаление сессии.
     */
    public function destroy()
    {
        $this->session->destroy();
    }
}

