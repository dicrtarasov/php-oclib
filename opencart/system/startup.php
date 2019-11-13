<?php

/** @noinspection PhpUnhandledExceptionInspection */

// Check Version
use app\models\Categ;
use yii\caching\TagDependency;
use yii\log\Logger;

if (version_compare(phpversion(), '5.3.0', '<') == true) {
    exit('PHP5.3+ Required');
}

// Magic Quotes Fix
if (ini_get('magic_quotes_gpc')) {
    function clean($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[clean($key)] = clean($value);
            }
        } else {
            $data = stripslashes($data);
        }

        return $data;
    }

    $_GET = clean($_GET);
    $_POST = clean($_POST);
    $_COOKIE = clean($_COOKIE);
}

if (! ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// Windows IIS Compatibility
if (! isset($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $_SERVER['DOCUMENT_ROOT'] =
            str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
    }
}

if (! isset($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['PATH_TRANSLATED'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/',
            substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
    }
}

if (! isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);

    if (isset($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }
}

if (! isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = getenv('HTTP_HOST');
}

// Check if SSL
$_SERVER['HTTPS'] = in_array($_SERVER['HTTPS'] ?? '', ['on', '1'], false) ||
    ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ||
    ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on';

// Autoloader
function library($class)
{
    $file = DIR_SYSTEM . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';
    if (is_file($file)) {
        include_once($file);
        return true;
    } else {
        return false;
    }
}

function vendor($class)
{
    $file = DIR_SYSTEM . 'vendor/' . str_replace('\\', '/', strtolower($class)) . '.php';
    if (is_file($file)) {
        include_once($file);
        return true;
    } else {
        return false;
    }
}

spl_autoload_register('library');
spl_autoload_register('vendor');
spl_autoload_extensions('.php');

// Composer
require_once(__DIR__ . '/../vendor/autoload.php');

// Engine
require_once(DIR_SYSTEM . 'engine/action.php');
require_once(DIR_SYSTEM . 'engine/controller.php');
require_once(DIR_SYSTEM . 'engine/event.php');
require_once(DIR_SYSTEM . 'engine/front.php');
require_once(DIR_SYSTEM . 'engine/loader.php');
require_once(DIR_SYSTEM . 'engine/model.php');
require_once(DIR_SYSTEM . 'engine/registry.php');

// Helper
require_once(DIR_SYSTEM . 'helper/general.php');
require_once(DIR_SYSTEM . 'helper/json.php');
require_once(DIR_SYSTEM . 'helper/utf8.php');

// Composer
require(__DIR__ . '/../vendor/autoload.php');

// константы YII_ENV и YII_DEBUG должны быть установлены до загрузки Yii
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', DEBUG);

// подключаем класс Yii
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

// создаем приложение Yii
new yii\web\Application(require(__DIR__ . '/../config/yii.web.php'));



