<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

use function get_class;

/**
 * Ошибка валидации.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class ValidateException extends Exception
{
    /**
     * Консруктор.
     *
     * @param \dicr\oclib\ArrayObject $obj
     * @param string $field
     * @param string $message
     */
    public function __construct($obj, string $field, string $message = null)
    {
        $msg = sprintf('Ошибка валидации: %s в %s', $field, get_class($obj));
        $msg .= ': ' . ($message ?? ($obj->{$field} ?? 'null'));

        parent::__construct($msg);
    }
}
