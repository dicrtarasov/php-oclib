<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

$config = require(__DIR__ . '/yii.php');

$config['components']['request'] = [
    'cookieValidationKey' => 'D4QXXXXXXXXXXXXQ4Zj7sf'
];

$config['components']['session'] = [
    'timeout' => 3600 * 24 * 7,
    'cookieParams' => [
        'lifetime' => 3600 * 24 * 7
    ]
];

return $config;


