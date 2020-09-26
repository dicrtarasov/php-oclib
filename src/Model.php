<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 26.09.20 22:16:39
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Class Model.
 */
abstract class Model implements RegistryProps
{
    use RegistryProxy;
}
