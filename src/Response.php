<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 19:25:18
 */

declare(strict_types = 1);
namespace dicr\oclib;

use yii\db\Exception;

use function ob_end_clean;
use function ob_get_level;

/**
 * Class Response.
 *
 * @noinspection MissingPropertyAnnotationsInspection
 */
class Response extends \yii\web\Response
{
    /**
     * Добавление заголовка.
     *
     * @param $header
     * @throws Exception
     */
    public function addHeader($header) : void
    {
        $matches = null;
        if (preg_match('~^\s*([^:]+)\s*:\s*(.+)\s*$~usm', $header, $matches)) {
            $this->headers->add(trim($matches[1]), trim($matches[2]));
        } elseif (preg_match('~^HTTP/[\d.]+\s+(\d+)~um', $header, $matches)) {
            $this->statusCode = (int)$matches[1];
        } else {
            throw new Exception('Некорректный заголовок: ' . $header);
        }
    }

    /**
     * Очищает выходной буфер.
     */
    public static function cleanOutput() : void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
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
        return (string)$this->content;
    }

    /**
     * Устанавливает выходные данные.
     *
     * @param mixed $output
     */
    public function setOutput($output) : void
    {
        $this->content = (string)$output;
    }

    /**
     * Отправка ответа.
     */
    public function output() : void
    {
        $this->send();
    }
}
