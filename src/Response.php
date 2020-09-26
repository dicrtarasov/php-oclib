<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 22:45:43
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\di\Instance;

/**
 * Class Response
 */
class Response
{
    /** @var \yii\web\Response компонент Yii */
    public $response = 'response';

    /**
     * Конструктор.
     *
     * @throws InvalidConfigException
     */
    public function __construct()
    {
        $this->response = Instance::ensure($this->response, \yii\web\Response::class);
    }

    /**
     * Добавление заголовка.
     *
     * @param $header
     * @throws Exception
     */
    public function addHeader($header)
    {
        $matches = null;
        if (preg_match('~^\s*([^:]+)\s*:\s*(.+)\s*$~usm', $header, $matches)) {
            $this->response->headers->add(trim($matches[1]), trim($matches[2]));
        } elseif (preg_match('~^HTTP/[\d.]+\s+(\d+)~um', $header, $matches)) {
            $this->response->statusCode = (int)$matches[1];
        } else {
            throw new Exception('Некорректный заголовок: ' . $header);
        }
    }

    /**
     * Переадресация.
     *
     * @param array|string $url
     * @param ?int $status
     */
    public function redirect($url, ?int $status = null) : void
    {
        $url = str_replace(['&amp;', "\n", "\r"], ['&', '', ''], $url);

        try {
            Yii::$app->end(0, $this->response->redirect($url, $status ?: 302));
        } catch (Throwable $ex) {
            Yii::error($ex, __METHOD__);
            exit;
        }
    }

    /**
     * Установить уровень компрессии.
     *
     * @param int $level
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function setCompression(int $level) : void
    {
        // noop
    }

    /**
     * Возвращает выходные данные.
     *
     * @return string
     */
    public function getOutput() : string
    {
        return (string)$this->response->content;
    }

    /**
     * Устанавливает выходные данные.
     *
     * @param mixed $output
     */
    public function setOutput($output)
    {
        $this->response->content = (string)$output;
    }

    /**
     * Отправка ответа.
     */
    public function output() : void
    {
        $this->response->send();
    }
}
