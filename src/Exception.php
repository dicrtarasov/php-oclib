<?php
namespace dicr\oclib;

/**
 * Базовый Exception.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 *
 */
class Exception extends \Exception
{
    /**
     * Консруктор.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable $prev
     */
    public function __construct(string $message = '', int $code = 500, \Throwable $prev = null)
    {
        parent::__construct($message, $code, $prev);
    }

    /**
     * Очищает буфер обмена.
     */
    protected static function cleanBuffer()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Обработка исключения.
     */
    public function process()
    {
        self::cleanBuffer();
        header('Content-Type: text/plain; charset=UTF-8', true, $this->getCode() ?: 500);

        if (DEBUG) {
            if (!empty($this->getPrevious())) {
                if (!empty($this->getMessage())) {
                    echo $this->getMessage() . "\n";
                }

                echo (string)$this->getPrevious() . "\n";
            } else {
                echo (string)$this;
            }
        } else {
            echo $this->getMessage() . "\n";
        }

        exit;
    }
}