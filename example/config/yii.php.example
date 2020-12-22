<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types=1);

/**
 * Общий конфиг Yii
 */

require_once(__DIR__ . '/common.php');

return [
    'id' => DOMAIN,
    'name' => 'РТК-НТ',
    'language' => 'ru',
    'sourceLanguage' => 'ru',
    'basePath' => DIR_HOME,
    'timeZone' => date_default_timezone_get(),

    'components' => [
        'cache' => [
            'class' => yii\caching\FileCache::class,
            'directoryLevel' => 2,
            'defaultDuration' => 2592000,
        ],

        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=' . DB_DATABASE,
            'username' => DB_USERNAME,
            'password' => DB_PASSWORD,
            'charset' => 'utf8',
            'tablePrefix' => DB_PREFIX,
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 2592000,
            'enableQueryCache' => true,
            'queryCacheDuration' => 2592000
        ],

        'log' => [
            'class' => yii\log\Dispatcher::class,
            'traceLevel' => YII_DEBUG ? 5 : 0,
            'targets' => [
                'file' => [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning', /*'info'*/]
                ]
            ]
        ],

        'formatter' => [
            'class' => app\components\Formatter::class
        ],

        'mailer' => [
            'class' => yii\swiftmailer\Mailer::class,
            'useFileTransport' => false,
            'enableSwiftMailerLogging' => true,
            'transport' => [
                'class' => Swift_SmtpTransport::class,
                'host' => 'localhost',
                'port' => '25'
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
