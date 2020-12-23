<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 19:24:54
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Прокси сессии OpeCart на Yii.
 */
class Session extends \yii\web\Session
{
    /** @var array */
    public $data;

    /**
     * Запуск сессии.
     *
     * @return string ID сессии
     */
    public function start() : string
    {
        $this->open();
        $this->data = &$_SESSION;

        return $this->getId();
    }
}

