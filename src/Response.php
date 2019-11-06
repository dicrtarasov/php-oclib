<?php
namespace dicr\oclib;

use yii\db\Exception;

class Response extends \yii\web\Response
{
    /**
     * Добавление заголовка.
     *
     * @param $header
     * @throws \yii\db\Exception
     */
    public function addHeader($header)
    {
        $matches = null;
        if (!preg_match('~^\s*([^\:]+)\s*\:\s*(.+)\s*$~uism', $header, $matches)) {
            throw new Exception('Некорректный заголовок: ' . $header);
        }

        $this->headers->add(trim($matches[1]), trim($matches[2]));
    }

    /**
     * Редирект.
     *
     * @param array|string $url
     * @param int $status
     * @return void|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function redirect($url, $status = 302, $checkAjax = true)
    {
        $url = str_replace(['&amp;', "\n", "\r"], ['&', '', ''], $url);
        parent::redirect($url, $status)->send();
        \Yii::$app->end();
    }

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
     * @throws \yii\base\InvalidConfigException
     */
    public function getOutput()
    {
        $this->prepare();
        return $this->content;
    }

    /**
     * Устанавливает выходные данные.
     *
     * @param $output
     */
    public function setOutput($output)
    {
        $this->content = $output;
    }

    /**
     * Отправка овета.
     */
    public function output()
    {
        if (! defined('HTTP_CATALOG')) {
            $this->content = str_replace('index.php?route=common/home', '', $this->content);
        }

        $this->send();
    }

    /**
     * Сжатие конента ответа.
     *
     * @param $data
     * @param int $level
     * @return false|string
     * @throws \yii\db\Exception
     */
    private function compress($data, $level = 0)
    {
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
            $encoding = 'gzip';
        }

        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
            $encoding = 'x-gzip';
        }

        if (! isset($encoding) || ($level < - 1 || $level > 9)) {
            return $data;
        }

        if (! extension_loaded('zlib') || ini_get('zlib.output_compression')) {
            return $data;
        }

        if (headers_sent()) {
            return $data;
        }

        if (connection_status()) {
            return $data;
        }

        $this->addHeader('Content-Encoding: ' . $encoding);

        return gzencode($data, (int)$level);
    }
}
