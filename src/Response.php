<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Yii;
use yii\db\Exception;
use yii\di\Instance;

/**
 * Class Response
 *
 * @package dicr\oclib
 */
class Response
{
    /** @var \yii\web\Response компонент Yii */
    public $response = 'response';

    /**
     * Конструктор.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct()
    {
        $this->response = Instance::ensure($this->response, \yii\web\Response::class);
    }

    /**
     * Добавление заголовка.
     *
     * @param $header
     * @throws \yii\db\Exception
     */
    public function addHeader($header)
    {
        $matches = null;
        if (! preg_match('~^\s*([^:]+)\s*:\s*(.+)\s*$~usm', $header, $matches)) {
            throw new Exception('Некорректный заголовок: ' . $header);
        }

        $this->response->headers->add(trim($matches[1]), trim($matches[2]));
    }

    /**
     * Редирект.
     *
     * @param array|string $url
     * @param int $status
     * @return void|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function redirect($url, $status = 302)
    {
        $url = str_replace(['&amp;', "\n", "\r"], ['&', '', ''], $url);
        Yii::$app->end(0, $this->response->redirect($url, $status));
    }

    /** @noinspection PhpMethodMayBeStaticInspection */

    /**
     * Усановить уровнь компрессии.
     *
     * @param $level
     */
    public function setCompression($level)
    {

    }

    /**
     * Возвращает выходные данные.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->response->content;
    }

    /**
     * Устанавливает выходные данные.
     *
     * @param $output
     */
    public function setOutput($output)
    {
        $this->response->content = $output;
    }

    /**
     * Отправка овета.
     */
    public function output()
    {
        $this->response->send();
    }
}
