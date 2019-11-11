<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

/**
 * Общий конфиг Yii
 */

require_once(__DIR__ . '/common.php');

defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', DEBUG);

return [
    'id' => DOMAIN,
    'name' => APP_NAME,
    'language' => 'ru',
    'sourceLanguage' => 'ru',
    'basePath' => DIR_HOME,
    'defaultRoute' => 'common/home',
    'timeZone' => date_default_timezone_get(),

    'components' => [
        'cache' => [
            //'class' => yii\caching\DummyCache::class,
            'class' => yii\caching\FileCache::class,
            //'class' => yii\caching\ApcCache::class,
            //'useApcu' => true,
            //'class' => yii\caching\MemCache::class,
            //'useMemcached' => false,
            'defaultDuration' => 86400,
            //'keyPrefix' => DOMAIN,
        ],

        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=' . DB_DATABASE,
            'username' => DB_USERNAME,
            'password' => DB_PASSWORD,
            'charset' => 'utf8',
            'tablePrefix' => DB_PREFIX,
            'enableSchemaCache' => true,
            'schemaCache' => 'cache',
            'schemaCacheDuration' => 2592000,
            'enableQueryCache' => true,
            'queryCache' => 'cache',
            'queryCacheDuration' => 86400
        ],

        'log' => [
            'class' => yii\log\Dispatcher::class,
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                'file' => [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning', /*'info', 'trace', 'profile'*/]
                ]
            ]
        ],

        'urlManager' => [
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
        ]
    ],
    'bootstrap' => ['log']
];
