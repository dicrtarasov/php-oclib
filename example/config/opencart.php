<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

/**
 * Общий конфиг OpenCart.
 */

require_once(__DIR__ . '/common.php');

define('DIR_SYSTEM', DIR_HOME . '/system/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_IMAGE', DIR_HOME . '/image/');
define('DIR_CACHE', DIR_SYSTEM . 'storage/cache/');
define('DIR_DOWNLOAD', DIR_SYSTEM . 'storage/download/');
define('DIR_LOGS', DIR_HOME . '/../logs/');
define('DIR_UPLOAD', DIR_SYSTEM . 'storage/upload/');

// HTTP
define('HTTP_CATALOG',
    sprintf('http%s://%s/', ($_SERVER['SERVER_PORT'] ?? 80) === 443 ? 's' : '', $_SERVER['HTTP_HOST'] ?? ''));

const HTTPS_CATALOG = HTTP_CATALOG;

const DB_DRIVER = 'mysqli';
const DB_PORT = '3306';
