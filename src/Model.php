<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 23.12.20 18:18:11
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
