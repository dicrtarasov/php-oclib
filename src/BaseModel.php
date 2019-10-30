<?php

/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Базовая модель OpenCart.
 *
 * @package dicr\oclib
 */
abstract class BaseModel extends RegistryProxy
{
    /**
     * BaseModel constructor.
     */
    public function __construct()
    {
        parent::__construct([]);
    }
}
