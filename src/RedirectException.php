<?php
namespace dicr\oclib;

/**
 * Исключение переадресации.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class RedirectException extends Exception
{
    /**
     * Консруктор.
     *
     * @param string $location
     * @param int $code
     * @param \Throwable $prev
     */
    public function __construct(string $location, int $code = 303, \Throwable $prev = null)
    {
        parent::__construct($location, $code, $prev);
    }

    /**
     * {@inheritDoc}
     * @see Exception::process()
     */
    public function process()
    {
        self::cleanBuffer();
        header('Location: ' . $this->getMessage(), true, $this->getCode());
        exit;
    }
}