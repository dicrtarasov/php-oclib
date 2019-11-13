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
    'cookieValidationKey' => 'D4Q_V4235rQWIMBZn-cF34HVAQ4Zj7sf'
];

$config['components']['session'] = [
    'timeout' => 3600 * 24 * 7,
    'cookieParams' => [
        'lifetime' => 3600 * 24 * 7
    ]
];

$config['components']['urlManager'] = [
    'class' => yii\web\UrlManager::class,
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'routeParam' => 'route',
    'rules' => [
        '' => 'common/home',
        'search' => 'product/search',
        'cart' => 'checkout/cart',
        'price' => 'tool/price',
        'account' => 'account/account',
        'register' => 'account/register',
        'login' => 'account/login',
        'logout' => 'account/logout',
        'news' => 'information/news',
        'review' => 'information/review',
        'catalogue' => 'product/catalogue',
        'categories' => 'common/categs',
        'contacts' => 'information/contact',
        'posts' => 'information/posts',
        'brands' => 'product/manufacturer',
        'services' => 'service/service',
        [
            'class' => app\components\UrlAliasRule::class
        ]
    ]
];

return $config;

