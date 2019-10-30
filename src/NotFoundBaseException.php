<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnused */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Исключение 404.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class NotFoundBaseException extends BaseException
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
     * @see BaseException::process()
     */
    public function process()
    {
        self::cleanBuffer();
        http_response_code(404);
        echo BaseRegistry::app()->load->view('error/not_found');
        exit;
    }
}
