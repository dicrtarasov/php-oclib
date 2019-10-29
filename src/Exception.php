<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use Throwable;

/**
 * Базовый Exception.
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
    public function __construct(string $message = '', int $code = 500, Throwable $prev = null)
    {
        parent::__construct($message, $code, $prev);
    }

    /**
     * Обработка исключения.
     */
    public function process()
    {
        self::cleanBuffer();
        header('Content-Type: text/plain; charset=UTF-8', true, $this->getCode() ?: 500);

        /** @noinspection PhpUndefinedConstantInspection */
        if (DEBUG) {
            if ($this->getPrevious() !== null) {
                if (! empty($this->getMessage())) {
                    echo $this->getMessage() . "\n";
                }

                echo $this->getPrevious() . "\n";
            } else {
                echo (string)$this;
            }
        } else {
            echo $this->getMessage() . "\n";
        }

        exit;
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
}
