<?php
namespace dicr\oclib;

/**
 * Исключение 404.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class NotFoundException extends Exception
{
    /**
     * Конструктор.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }

    /**
     * {@inheritDoc}
     * @see Exception::process()
     */
    public function process()
    {
        self::cleanBuffer();
        http_response_code(404);
        echo Registry::app()->load->view('error/not_found');
        exit;
    }
}
