<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 22:50:31
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Class Model.
 */
abstract class Model implements RegistryProps
{
    use RegistryProxy;

    /**
     * Model constructor.
     *
     * @param ?Registry $registry
     */
    public function __construct(?Registry $registry = null)
    {
        // noop
    }
}
