<?php
/**
 * @copyright 2019-2019 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 11.12.19 23:54:30
 */

declare(strict_types=1);

/**
 * Общий конфиг OpenCart.
 */

require_once(__DIR__ . '/common.php');

// DIRS
define('DIR_SYSTEM', DIR_HOME . '/system/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_IMAGE', DIR_HOME . '/image/');
define('DIR_CACHE', DIR_SYSTEM . 'storage/cache/');
define('DIR_DOWNLOAD', DIR_SYSTEM . 'storage/download/');
define('DIR_LOGS', DIR_HOME . '/../logs/');
define('DIR_UPLOAD', DIR_SYSTEM . 'storage/upload/');

// HTTP
define('HTTP_CATALOG', sprintf('%s://%s/', SCHEME, $_SERVER['HTTP_HOST'] ?? DOMAIN));
const HTTPS_CATALOG = HTTP_CATALOG;

// DB
const DB_DRIVER = 'mysqli';
const DB_PORT = '3306';
