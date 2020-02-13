<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

/**
 * Конфиг-файл для web-приложения catalog.
 */

$config = require(__DIR__ . '/yii.php');

$config['defaultRoute'] = 'common/home';

$config['components']['request'] = [
    'enableCookieValidation' => false // не работает для чужих cookie
];

$config['components']['session'] = [
    'timeout' => 3600 * 24 * 7,
    'cookieParams' => [
        'domain' => '.' . $_SERVER['HTTP_HOST'],
        'lifetime' => 3600 * 24 * 7
    ]
];

return $config;

