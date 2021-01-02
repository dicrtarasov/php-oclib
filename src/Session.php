<?php
/**
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 02.01.21 22:03:33
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\base\InvalidConfigException;
use yii\di\Instance;

/**
 * Прокси сессии OpeCart на Yii.
 */
class Session
{
    /** @var \yii\web\Session компонент Yii */
    public $session = 'session';

    /** @var array */
    public $data;

    /**
     * Session constructor.
     *
     * @throws InvalidConfigException
     */
    public function __construct()
    {
        $this->session = Instance::ensure($this->session, \yii\web\Session::class);

        $this->session->open();
    }

    /**
     * Запуск сессии.
     *
     * @return string ID сессии
     */
    public function start(): string
    {
        $this->session->open();
        $this->data = &$_SESSION;

        return $this->getId();
    }

    /**
     * Возвращает ID сессии.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->session->id;
    }

    /**
     * Закрытие сессии.
     */
    public function close(): void
    {
        $this->session->close();
    }

    /**
     * Удаление сессии.
     */
    public function destroy(): void
    {
        $this->session->destroy();
    }
}

