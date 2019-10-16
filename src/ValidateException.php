<?php
namespace dicr\oclib;

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
        $msg .= ': ' . ($message ?? ($this->$field ?? 'null'));

        parent::__construct($msg);
    }
}
