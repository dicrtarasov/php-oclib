<?php
namespace dicr\oclib;

/**
 * Базовый объект.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class ArrayObject implements \ArrayAccess
{
    /**
     * Консруктор.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);
    }

    /**
     * Конфигурирование значений объекта.
     *
     * @param array $config
     */
    public function configure(array $config)
    {
        foreach ($config as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * {@inheritDoc}
     * @see \ArrayAccess::offsetExists()
     */
	public function offsetExists($offset)
	{
	    return property_exists($this, $offset);
	}

	/**
	 * {@inheritDoc}
	 * @see \ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset)
	{
	    return $this->$offset;
	}

	/**
	 * {@inheritDoc}
	 * @see \ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value)
	{
	    $this->$offset = $value;
	}

	/**
	 * {@inheritDoc}
	 * @see \ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset)
	{
	    unset($this->$offset);
	}
}