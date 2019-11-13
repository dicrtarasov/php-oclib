<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

/**
 * Общий конфиг для Yii и Opecart
 */

// подключаем локальный конфиг для тестового/рабочего сайта
require_once(__DIR__ . '/local.php');

// HOME
define('DIR_HOME', dirname(__DIR__));

// Прокси
const SERVICE_PROXY = [
    'tcp://95.181.183.234:8085',
    'tcp://95.181.183.240:8085',
    'tcp://185.101.71.222:8085',
    'tcp://188.68.1.227:8085',
    'tcp://185.101.68.212:8085'
];

// DEBUG
define('DEBUG_IPS', [
    'office' => gethostbyname('gw.up-advert.ru'),
    'dicr' => gethostbyname('gw.dicr.org')
]);

define('DEBUG', empty($_SERVER['REMOTE_ADDR']) || in_array($_SERVER['REMOTE_ADDR'], DEBUG_IPS, true));

// init
ini_set('display_errors', DEBUG ? '1' : '0');
setlocale(LC_ALL, 'ru_RU.UTF-8');
setlocale(LC_NUMERIC, 'C');
