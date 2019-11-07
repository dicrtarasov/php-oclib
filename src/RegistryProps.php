<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types = 1);
namespace dicr\oclib;

/**
 * Интерфейс динамических свойств объектов Registry и RegistryProxy.
 *
 * @property-read \Document $document
 * @property-read \Config $config
 * @property-read \dicr\oclib\Cache $cache
 * @property-read \dicr\oclib\DB $db
 * @property-read \dicr\oclib\Loader $load
 * @property-read \dicr\oclib\Url $url
 * @property-read \dicr\oclib\Request $request
 * @property-read \dicr\oclib\Response $response
 * @property-read \dicr\oclib\Session $session
 *
 * @package dicr\oclib
 */
interface RegistryProps
{

}
