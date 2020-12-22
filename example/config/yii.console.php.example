<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

$config = require(__DIR__ . '/yii.php');

// для консоли необходимо инициализировать алисасы web для генерации Url
const SCHEME = 'https';
const URL_BASE = SCHEME . '://' . DOMAIN;

// дополняем web-конфиг, отсутствующий в консольной версии
$_SERVER['HTTP_HOST'] = DOMAIN;
$_SERVER['REQUEST_SCHEME'] = SCHEME;

$config['aliases']['@web'] = URL_BASE;
$config['aliases']['@webroot'] = '@app';

$config['components']['urlManager']['baseUrl'] = URL_BASE;
$config['components']['urlManager']['hostInfo'] = URL_BASE;

$config['controllerNamespace'] = 'app\\commands';

$config['components']['log']['targets']['console'] = [
    'class' => dicr\log\ConsoleTarget::class,
    'levels' => ['error', 'warning', /*'profile'*/]
];

return $config;


