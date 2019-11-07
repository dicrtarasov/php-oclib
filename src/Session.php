<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnusedParameterInspection */

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
     * @param string $session_id
     * @param string $key
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($session_id = '', $key = 'default')
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

