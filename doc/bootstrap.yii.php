<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types = 1);
use yii\log\FileTarget;

defined('YII_ENV') or define('YII_ENV', 'dev');


defined('YII_DEBUG') or /** @noinspection PhpUndefinedConstantInspection */ define('YII_DEBUG', DEBUG);

/** @noinspection PhpIncludeInspection */
require(__DIR__ . '/vendor/autoload.php');
/** @noinspection PhpIncludeInspection */
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');

/** @noinspection PhpUndefinedConstantInspection */
$config = [
    'id' => 'rtk-nt',
    'basePath' => __DIR__,
    'components' => [
        yii\data\Sort::class => app\components\Sort::class,
        yii\data\Pagination::class => app\components\Pagination::class,

        'cache' => [
            'class' => yii\caching\FileCache::class,
            'defaultDuration' => 86400,
            //'keyPrefix' => DOMAIN,
            //'useApcu' => true
        ],

        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=' . DB_DATABASE,
            'username' => DB_USERNAME,
            'password' => DB_PASSWORD,
            'charset' => 'utf8',
            'enableSchemaCache' => true,
            'schemaCache' => 'cache',
            'schemaCacheDuration' => 86400,
            'enableQueryCache' => true,
            'queryCache' => 'cache',
            'queryCacheDuration' => 86400
        ],

        'log' => [
            'class' => yii\log\Dispatcher::class,
            'targets' => [
                [
                    'class' => FileTarget::class,
                    'levels' => ['warning', 'error']
                ]
            ]
        ]
    ]
];

new yii\web\Application($config);

