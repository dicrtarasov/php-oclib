#!/usr/bin/php7.2
<?php
/**
 * Copyright (c) 2019.
 *
 * @author Igor (Dicr) Tarasov, develop@dicr.org
 */

declare(strict_types = 1);

require_once(__DIR__ . '/config/common.php');
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', DEBUG);

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/config/yii.console.php');

/** @noinspection PhpUnhandledExceptionInspection */
(new yii\console\Application($config))->run();
